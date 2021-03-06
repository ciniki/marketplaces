<?php
//
// Description
// ===========
// This method will return all the information about an market.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the market is attached to.
// market_id:       The ID of the market to get the details for.
// 
// Returns
// -------
//
function ciniki_marketplaces_marketGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'market_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market'), 
        'sellers'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sellers'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['tnid'], 'ciniki.marketplaces.marketGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load marketplaces maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'maps');
    $rc = ciniki_marketplaces_maps($ciniki, $modules);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $strsql = "SELECT ciniki_marketplaces.id, "
        . "ciniki_marketplaces.name, "
        . "ciniki_marketplaces.status, "
        . "ciniki_marketplaces.status AS status_text, "
        . "ciniki_marketplaces.start_date, "
        . "ciniki_marketplaces.end_date "
        . "FROM ciniki_marketplaces "
        . "WHERE ciniki_marketplaces.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_marketplaces.id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.marketplaces', array(
        array('container'=>'markets', 'fname'=>'id', 'name'=>'market',
            'fields'=>array('id', 'name', 'status', 'status_text', 'start_date', 'end_date'),
            'maps'=>array('status_text'=>$maps['market']['status']),
            'utctotz'=>array('start_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'end_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['markets']) || !isset($rc['markets'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.6', 'msg'=>'Unable to find market'));
    }
    $market = $rc['markets'][0]['market'];

    $market['dates'] = $market['start_date'];
    if( $market['end_date'] != '' ) {
        $market['dates'] .= ' - ' . $market['end_date'];
    }

    if( isset($args['sellers']) && $args['sellers'] == 'summary' ) {
        //
        // Get the list of marketplaces
        //
        $strsql = "SELECT ciniki_marketplace_sellers.id, "
            . "ciniki_marketplace_sellers.status, "
            . "ciniki_marketplace_sellers.status AS status_text, "
            . "ciniki_marketplace_sellers.num_items, "
            . "IFNULL(ciniki_customers.display_name, '') AS display_name, "
            . "SUM(IFNULL(ciniki_marketplace_items.sell_price, 0)) AS total_price, "
            . "SUM(IFNULL(ciniki_marketplace_items.tenant_fee, 0)) AS total_tenant_fee, "
            . "SUM(IFNULL(ciniki_marketplace_items.seller_amount, 0)) AS total_seller_amount "
            . "FROM ciniki_marketplace_sellers "
            . "LEFT JOIN ciniki_marketplace_items ON ("
                . "ciniki_marketplace_sellers.id = ciniki_marketplace_items.seller_id "
                . "AND ciniki_marketplace_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_marketplace_sellers.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_marketplace_sellers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_marketplace_sellers.market_id = '" . ciniki_core_dbQuote($ciniki, $market['id']) . "' "
            . "GROUP BY ciniki_marketplace_sellers.id "
            . "ORDER BY ciniki_customers.display_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.marketplaces', array(
            array('container'=>'sellers', 'fname'=>'id', 'name'=>'seller',
                'fields'=>array('id', 'display_name', 'status', 'status_text', 'num_items',
                    'total_price', 'total_tenant_fee', 'total_seller_amount'),
                'maps'=>array('status_text'=>$maps['seller']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['sellers']) ) {
            $market['sellers'] = array();
        } else {
            $market['sellers'] = $rc['sellers'];
            $market['totals'] = array('items'=>0, 'value'=>0, 'fees'=>0, 'net'=>0);
            foreach($market['sellers'] as $sid => $seller) {
                $market['sellers'][$sid]['seller']['total_price'] = numfmt_format_currency($intl_currency_fmt, 
                    $seller['seller']['total_price'], $intl_currency);
                $market['sellers'][$sid]['seller']['total_tenant_fee'] = numfmt_format_currency($intl_currency_fmt, 
                    $seller['seller']['total_tenant_fee'], $intl_currency);
                $market['sellers'][$sid]['seller']['total_seller_amount'] = numfmt_format_currency($intl_currency_fmt, 
                    $seller['seller']['total_seller_amount'], $intl_currency);
                $market['totals']['items'] += $seller['seller']['num_items'];
                $market['totals']['value'] = bcadd($market['totals']['value'], $seller['seller']['total_price'], 2);
                $market['totals']['fees'] = bcadd($market['totals']['fees'], $seller['seller']['total_tenant_fee'], 2);
                $market['totals']['net'] = bcadd($market['totals']['net'], $seller['seller']['total_seller_amount'], 2);
            }
            $market['totals']['value'] = numfmt_format_currency($intl_currency_fmt, $market['totals']['value'], $intl_currency);
            $market['totals']['fees'] = numfmt_format_currency($intl_currency_fmt, $market['totals']['fees'], $intl_currency);
            $market['totals']['net'] = numfmt_format_currency($intl_currency_fmt, $market['totals']['net'], $intl_currency);
        }
    }

    return array('stat'=>'ok', 'market'=>$market);
}
?>
