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
function ciniki_marketplaces_marketSellerList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'market_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['tnid'], 'ciniki.marketplaces.marketSellerList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    
    //
    // Get the list of marketplaces
    //
    $strsql = "SELECT ciniki_marketplace_sellers.id, "
        . "ciniki_customers.display_name, "
        . "ciniki_marketplace_sellers.num_items, "
        . "SUM(ciniki_marketplace_items.price) AS total_price, "
        . "SUM(ciniki_marketplace_items.sell_price) AS total_sell_price "
        . "SUM(ciniki_marketplace_items.tenant_fee) AS total_tenant_fee, "
        . "SUM(ciniki_marketplace_items.seller_amount) AS total_seller_amount, "
        . "FROM ciniki_marketplace_sellers "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_marketplace_sellers.customer_id = ciniki_customers.customer_id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_marketplace_items ON ("
            . "ciniki_marketplace_items.market_id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
            . "AND ciniki_marketplace_sellers.customer_id = ciniki_marketplace_items.customer_id "
            . "AND ciniki_marketplace_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_marketplace_sellers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_customers.display_name "
        . "GROUP BY customer_id "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.marketplaces', array(
        array('container'=>'sellers', 'fname'=>'id', 'name'=>'seller',
            'fields'=>array('id', 'display_name', 'num_items', 'total_price', 'total_sell_price', 'total_tenant_fee', 'total_seller_amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['sellers']) ) {
        $sellers = array();
    } else {
        $sellers = $rc['sellers'];
        foreach($sellers as $sid => $seller) {
            $sellers[$sid]['seller']['total_price'] = numfmt_format_currency($intl_currency_fmt, 
                $seller['seller']['total_price'], $intl_currency);
            $sellers[$sid]['seller']['total_sell_price'] = numfmt_format_currency($intl_currency_fmt, 
                $seller['seller']['total_sell_price'], $intl_currency);
            $sellers[$sid]['seller']['total_tenant_fee'] = numfmt_format_currency($intl_currency_fmt, 
                $seller['seller']['total_tenant_fee'], $intl_currency);
            $sellers[$sid]['seller']['total_seller_amount'] = numfmt_format_currency($intl_currency_fmt, 
                $seller['seller']['total_seller_amount'], $intl_currency);
        }
    }
    
    return array('stat'=>'ok', 'sellers'=>$sellers);
}
?>
