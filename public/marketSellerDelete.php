<?php
//
// Description
// -----------
// This method will delete a marketplace seller from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the marketplace is attached to.
// seller_id:           The ID of the seller to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_marketplaces_marketSellerDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'seller_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Seller'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $ac = ciniki_marketplaces_checkAccess($ciniki, $args['business_id'], 'ciniki.marketplaces.marketSellerDelete');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Get the uuid of the marketplace seller to be deleted
    //
    $strsql = "SELECT uuid, market_id, customer_id "
        . "FROM ciniki_marketplace_sellers "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'seller');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['seller']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.11', 'msg'=>'The seller does not exist'));
    }
    $seller_uuid = $rc['seller']['uuid'];
    $market_id = $rc['seller']['market_id'];
    $customer_id = $rc['seller']['customer_id'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.marketplaces');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove the customer items 
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_marketplace_items "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND seller_id = '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $rows = $rc['rows'];
        
        foreach($rows as $rid => $row) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.marketplaces.item', 
                $row['id'], $row['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
                return $rc; 
            }
        }
    }

    //
    // Remove the marketplace seller
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.marketplaces.seller', $args['seller_id'], $seller_uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.marketplaces');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'marketplaces');

    return array('stat'=>'ok');
}
?>
