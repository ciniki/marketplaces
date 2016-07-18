<?php
//
// Description
// -----------
// This method will return the list of marketplaces for a business.  It is restricted
// to business owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to get marketplaces for.
//
// Returns
// -------
//
function ciniki_marketplaces_marketList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['business_id'], 'ciniki.marketplaces.marketList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    
    //
    // Get the list of marketplaces
    //
    $strsql = "SELECT id, name, status, start_date, end_date, "
        . "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
        . "DATE_FORMAT(end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
        . "FROM ciniki_marketplaces "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_marketplaces.start_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.marketplaces', array(
        array('container'=>'markets', 'fname'=>'id', 'name'=>'market',
            'fields'=>array('id', 'name', 'status', 'start_date', 'end_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['markets']) ) {
        $markets = array();
    } else {
        $markets = $rc['markets'];
    }
    
    return array('stat'=>'ok', 'markets'=>$markets);
}
?>
