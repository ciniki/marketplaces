<?php
//
// Description
// ===========
// This method will update an marketplace in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the marketplace is attached to.
// name:            (optional) The new name of the marketplace.
// url:             (optional) The new URL for the marketplace website.
// description:     (optional) The new description for the marketplace.
// start_date:      (optional) The new date the marketplace starts.  
// end_date:        (optional) The new date the marketplace ends, if it's longer than one day.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_marketplaces_marketSellerUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'seller_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Seller'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'), 
        'num_items'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Number of Items'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['business_id'], 'ciniki.marketplaces.marketSellerUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load existing Seller
    //
    $strsql = "SELECT uuid, market_id, customer_id, status, flags "
        . "FROM ciniki_marketplace_sellers "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'seller');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['seller']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.14', 'msg'=>'That seller does not exist'));
    }
    $seller = $rc['seller'];
  
    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.marketplaces');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check if the customer_id is changing
    //
    if( isset($args['customer_id']) && $args['customer_id'] != $seller['customer_id'] ) {
        //
        // Check if that customer already exists for this market
        //
        $strsql = "SELECT id, market_id, customer_id, status, flags "
            . "FROM ciniki_marketplace_sellers "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' "
            . "AND market_id = '" . ciniki_core_dbQuote($ciniki, $seller['market_id']) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'seller');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
            return $rc;
        }
        if( isset($rc['seller']) && $rc['seller']['customer_id'] == $args['customer_id'] ) {
            $new_seller = $rc['seller'];
            //
            // Update all the items for the old seller to link to the new seller
            //
            $strsql = "SELECT id, seller_id, market_id "
                . "FROM ciniki_marketplace_items "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "AND market_id = '" . ciniki_core_dbQuote($ciniki, $seller['market_id']) . "' "
                . "AND seller_id = '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'item');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
                return $rc;
            }
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.marketplaces.item', $row['id'], array(
                        'seller_id'=>$new_seller['id']
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
                        return $rc;
                    }
                }
            }
            //
            // Remove the old seller
            //
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.marketplaces.seller', $args['seller_id'], $seller['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
                return $rc;
            }
        }
    }

    //
    // Make sure a new seller wasn't found, then update current seller
    //
    if( !isset($new_seller) ) {
        //
        // Update the marketplace in the database
        //
        $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.marketplaces.seller', $args['seller_id'], $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
            return $rc;
        }
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

    if( isset($new_seller) ) {
        return array('stat'=>'ok', 'new_seller_id'=>$new_seller['id']);
    } 

    return array('stat'=>'ok');
}
?>
