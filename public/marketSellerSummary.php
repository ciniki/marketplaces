<?php
//
// Description
// ===========
// This method returns the pdf of the seller summaries, or
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the seller is attached to.
// 
// Returns
// -------
//
function ciniki_marketplaces_marketSellerSummary($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'market_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market'),
        'seller_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Seller'),
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
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['tnid'], 'ciniki.marketplaces.marketSellerSummary'); 
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
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Load marketplaces maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'maps');
    $rc = ciniki_marketplaces_maps($ciniki, $modules);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Get the market name
    //
    $strsql = "SELECT name "
        . "FROM ciniki_marketplaces "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'market');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['market']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.13', 'msg'=>'Market does not exist'));
    }
    $market_name = $rc['market']['name'];

    //
    // Get the list of sellers and their items
    //
    $strsql = "SELECT ciniki_marketplace_sellers.id, "  
        . "ciniki_marketplace_sellers.customer_id, "
        . "ciniki_marketplace_sellers.num_items, "
        . "ciniki_customers.display_name, "
        . "ciniki_marketplace_items.id AS item_id, "
        . "ciniki_marketplace_items.code, "
        . "ciniki_marketplace_items.type, "
        . "ciniki_marketplace_items.name, "
        . "ciniki_marketplace_items.price, "
        . "ciniki_marketplace_items.fee_percent, "
        . "DATE_FORMAT(ciniki_marketplace_items.sell_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sell_date, "
        . "ciniki_marketplace_items.sell_price, "
        . "ciniki_marketplace_items.tenant_fee, "
        . "ciniki_marketplace_items.seller_amount "
        . "FROM ciniki_marketplace_sellers "
        . "LEFT JOIN ciniki_marketplace_items ON ("
            . "ciniki_marketplace_sellers.id = ciniki_marketplace_items.seller_id "
            . "AND ciniki_marketplace_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_marketplace_sellers.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_marketplace_sellers.market_id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
        . "AND ciniki_marketplace_sellers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['seller_id']) && $args['seller_id'] != '' ) {
        $strsql .= "AND ciniki_marketplace_sellers.id = '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' ";
    }
    $strsql .= "ORDER BY display_name, code, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.marketplaces', array(
        array('container'=>'sellers', 'fname'=>'id',
            'fields'=>array('id', 'display_name', 'num_items')),
        array('container'=>'items', 'fname'=>'item_id',
            'fields'=>array('id'=>'item_id', 'code', 'name', 'type',
                'price', 'fee_percent', 'sell_date', 'sell_price', 'tenant_fee', 'seller_amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sellers']) ) {
        $sellers = array(); 
    } else {
        $sellers = $rc['sellers'];
    }

    if( isset($args['seller_id']) && $args['seller_id'] != '' ) {
        $title = $market_name;
    }

    $today = new DateTime('now', new DateTimeZone($intl_timezone));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'templates', 'sellersummary');
    $rc = ciniki_marketplaces_templates_sellersummary($ciniki, $args['tnid'], array(
        'title'=>$market_name,
        'author'=>$tenant_details['name'],
        'footer'=>$today->format('M d, Y'),
        'sellers'=>$sellers,
        ));
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $pdf = $rc['pdf'];

    //
    // Output the pdf
    //
    $filename = $market_name . ' - Price List - ' . $today->format('M d, Y');
    $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $filename);
    ini_set('display_errors', 1);
    ini_set('html_errors', 1);
    $pdf->Output($filename, 'D');

    return array('stat'=>'exit');
}
?>
