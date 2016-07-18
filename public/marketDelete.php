<?php
//
// Description
// -----------
// This method will delete a marketplace from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the marketplace is attached to.
// marketplace_id:          The ID of the marketplace to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_marketplaces_marketDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'market_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Market'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $ac = ciniki_marketplaces_checkAccess($ciniki, $args['business_id'], 'ciniki.marketplaces.marketDelete');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Get the uuid of the marketplace to be deleted
    //
    $strsql = "SELECT uuid, status FROM ciniki_marketplaces "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'marketplace');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['marketplace']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2110', 'msg'=>'The marketplace does not exist'));
    }
    $marketplace_uuid = $rc['marketplace']['uuid'];
    $marketplace_status = $rc['marketplace']['status'];

    //
    // The marketplace can only be deleted if it's first marked deleted
    //
    if( $marketplace_status < 50 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2102', 'msg'=>'The marketplace must be inactive before it can be deleted.'));
    }

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
    // Remove the sellers 
    //
    $strsql = "SELECT id, uuid FROM ciniki_marketplace_sellers "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND market_id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'seller');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $rows = $rc['rows'];
        
        foreach($rows as $rid => $row) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.marketplaces.seller', 
                $row['id'], $row['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
                return $rc; 
            }
        }
    }

    //
    // Remove the items from the marketplace
    //
    $strsql = "SELECT id, uuid FROM ciniki_marketplace_items "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND market_id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'seller');
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
    // Remove the marketplace
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.marketplaces.market', 
        $args['market_id'], $marketplace_uuid, 0x04);
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
