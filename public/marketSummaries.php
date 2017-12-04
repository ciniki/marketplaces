<?php
//
// Description
// -----------
// This method will return the list of marketplaces for a tenant.  It is restricted
// to tenant owners and sysadmins.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get marketplaces for.
//
// Returns
// -------
//
function ciniki_marketplaces_marketSummaries($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'year'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Year'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['tnid'], 'ciniki.marketplaces.marketSummaries');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    if( isset($args['year']) && $args['year'] != '' ) {
        $start_date = $args['year'] . '-01-01';
        $end_date = $args['year'] . '-12-31';
    }

    //
    // Get all the years of the marketplaces 
    //
    $strsql = "SELECT DISTINCT DATE_FORMAT(start_date, '%Y') AS year "
        . "FROM ciniki_marketplaces "
        . "WHERE ciniki_marketplaces.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY start_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.marketplaces', 'years', 'year');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['years']) ) {
        $years = $rc['years'];
    } else {
        $years = array();
    }
   
    //
    // Get the number of seller items
    //
    $strsql = "SELECT ciniki_marketplaces.id, "
        . "SUM(ciniki_marketplace_sellers.num_items) AS num_items "
        . "FROM ciniki_marketplaces "
        . "LEFT JOIN ciniki_marketplace_sellers ON ("
            . "ciniki_marketplaces.id = ciniki_marketplace_sellers.market_id "
            . "AND ciniki_marketplace_sellers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_marketplaces.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($start_date) && isset($end_date) ) {
        $strsql .= "AND ((ciniki_marketplaces.start_date >= '" . ciniki_core_dbQuote($ciniki, $start_date) . "' "
            . "AND ciniki_marketplaces.start_date <= '" . ciniki_core_dbQuote($ciniki, $end_date) . "' "
            . ") OR ("
            . "ciniki_marketplaces.end_date >= '" . ciniki_core_dbQuote($ciniki, $start_date) . "' "
            . "AND ciniki_marketplaces.end_date <= '" . ciniki_core_dbQuote($ciniki, $end_date) . "' "
            . ")) ";
    }
    $strsql .= "GROUP BY ciniki_marketplaces.id "
        . "ORDER BY ciniki_marketplaces.start_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.marketplaces', array(
        array('container'=>'markets', 'fname'=>'id', 'fields'=>array('id', 'num_items')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['markets']) ) {
        $market_items = $rc['markets'];
    }  else {
        $market_items = array();
    }

    //
    // Get the list of marketplaces
    //
    $strsql = "SELECT ciniki_marketplaces.id, "
        . "ciniki_marketplaces.name, "
        . "ciniki_marketplaces.status, "
        . "ciniki_marketplaces.start_date, "
        . "ciniki_marketplaces.end_date, "
        . "COUNT(ciniki_marketplace_items.id) AS num_sold, "
        . "SUM(IFNULL(ciniki_marketplace_items.sell_price, 0)) AS total_value, "
        . "SUM(IFNULL(ciniki_marketplace_items.tenant_fee, 0)) AS total_fees, "
        . "SUM(IFNULL(ciniki_marketplace_items.seller_amount, 0)) AS total_net "
        . "FROM ciniki_marketplaces "
//        . "LEFT JOIN ciniki_marketplace_sellers ON ("
//            . "ciniki_marketplaces.id = ciniki_marketplace_sellers.market_id "
//            . "AND ciniki_marketplace_sellers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//            . ") "
        . "LEFT JOIN ciniki_marketplace_items ON ("
            . "ciniki_marketplaces.id = ciniki_marketplace_items.market_id "
            . "AND ciniki_marketplace_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_marketplace_items.sell_date <> '0000-00-00' "
            . "";
    if( isset($start_date) && isset($end_date) ) {
        $strsql .= "AND ciniki_marketplace_items.sell_date >= '" . ciniki_core_dbQuote($ciniki, $start_date) . "' "
            . "AND ciniki_marketplace_items.sell_date <= '" . ciniki_core_dbQuote($ciniki, $end_date) . "' "
            . "";
    }
    $strsql .= ") "
        . "WHERE ciniki_marketplaces.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($start_date) && isset($end_date) ) {
        $strsql .= "AND ((ciniki_marketplaces.start_date >= '" . ciniki_core_dbQuote($ciniki, $start_date) . "' "
            . "AND ciniki_marketplaces.start_date <= '" . ciniki_core_dbQuote($ciniki, $end_date) . "' "
            . ") OR ("
            . "ciniki_marketplaces.end_date >= '" . ciniki_core_dbQuote($ciniki, $start_date) . "' "
            . "AND ciniki_marketplaces.end_date <= '" . ciniki_core_dbQuote($ciniki, $end_date) . "' "
            . ")) ";
    }
    $strsql .= "GROUP BY ciniki_marketplaces.id "
        . "ORDER BY ciniki_marketplaces.start_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.marketplaces', array(
        array('container'=>'markets', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'start_date', 'end_date',
                'num_sold', 'total_value', 'total_fees', 'total_net'),
            'utctotz'=>array('start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['markets']) ) {
        $markets = array();
        return array('stat'=>'ok', 'markets'=>$markets, 'years'=>$years);
    } 
    $markets = $rc['markets'];
    $totals = array('items'=>0, 'sold'=>0, 'value'=>0, 'fees'=>0, 'net'=>0);
    foreach($markets as $mid => $market) {
        if( isset($market_items[$market['id']]['num_items']) ) {
            $totals['items'] += $market_items[$market['id']]['num_items'];
            $markets[$mid]['num_items'] = $market_items[$market['id']]['num_items'];
        } else {
            $markets[$mid]['num_items'] = 0;
        }
        $totals['sold'] += $market['num_sold'];
        $totals['value'] = bcadd($totals['value'], $market['total_value'], 2);
        $totals['fees'] = bcadd($totals['fees'], $market['total_fees'], 2);
        $totals['net'] = bcadd($totals['net'], $market['total_net'], 2);
        $markets[$mid]['total_value'] = numfmt_format_currency($intl_currency_fmt, $market['total_value'], $intl_currency);
        $markets[$mid]['total_fees'] = numfmt_format_currency($intl_currency_fmt, $market['total_fees'], $intl_currency);
        $markets[$mid]['total_net'] = numfmt_format_currency($intl_currency_fmt, $market['total_net'], $intl_currency);
    }
    $totals['value'] = numfmt_format_currency($intl_currency_fmt, $totals['value'], $intl_currency);
    $totals['fees'] = numfmt_format_currency($intl_currency_fmt, $totals['fees'], $intl_currency);
    $totals['net'] = numfmt_format_currency($intl_currency_fmt, $totals['net'], $intl_currency);

    return array('stat'=>'ok', 'markets'=>$markets, 'totals'=>$totals, 'years'=>$years);
}
?>
