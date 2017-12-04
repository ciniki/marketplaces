<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an marketplace. 
// This method is typically used by the UI to display a list of changes that have occured 
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// market_id:           The ID of the marketplace to get the history for.
// field:               The field to get the history for. This can be any of the elements 
//                      returned by the ciniki.marketplaces.get method.
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Event Name" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_marketplaces_marketSellerHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'seller_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market Seller'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['tnid'], 'ciniki.marketplaces.marketSellerHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.marketplaces', 'ciniki_marketplace_history', $args['tnid'], 'ciniki_marketplace_sellers', $args['seller_id'], $args['field']);
}
?>
