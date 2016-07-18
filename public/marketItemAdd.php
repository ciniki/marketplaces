<?php
//
// Description
// -----------
// This method will add a new marketplace for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to add the marketplace to.
// name:            The name of the marketplace.
// status:          The status of the marketplace.
// start_date:      (optional) The date the marketplace starts.  
// end_date:        (optional) The date the marketplace ends, if it's longer than one day.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_marketplaces_marketItemAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'market_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market'), 
        'seller_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Seller'), 
        'code'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Code'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Type'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Category'), 
        'price'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 'name'=>'Price'), 
        'fee_percent'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Fee Percent'), 
        'sell_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'date', 'name'=>'Sell Date'), 
        'sell_price'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 'name'=>'Sell Price'), 
        'business_fee'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 'name'=>'Business Fee'), 
        'seller_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 'name'=>'Seller Amount'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['business_id'], 'ciniki.marketplaces.marketItemAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.marketplaces');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the marketplace to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.marketplaces.item', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.marketplaces');
        return $rc;
    }
    $item_id = $rc['id'];

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

    return array('stat'=>'ok', 'id'=>$item_id);
}
?>
