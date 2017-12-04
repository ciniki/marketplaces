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
function ciniki_marketplaces_marketItemList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'market_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market'), 
        'seller_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['tnid'], 'ciniki.marketplaces.marketItemList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    
    //
    // Get the list of market items
    //
    $strsql = "SELECT ciniki_marketplace_items.id, "
        . "ciniki_customers.display_name, "
        . "ciniki_marketplace_items.code, "
        . "ciniki_marketplace_items.name, "
        . "ciniki_marketplace_items.type, "
        . "ciniki_marketplace_items.category, "
        . "ciniki_marketplace_items.price, "
        . "ciniki_marketplace_items.fee_percent, "
        . "DATE_FORMAT(ciniki_marketplace_items.sell_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sell_date, "
        . "ciniki_marketplace_items.sell_price, "
        . "ciniki_marketplace_items.tenant_fee, "
        . "ciniki_marketplace_items.seller_amount, "
        . "ciniki_marketplace_items.notes "
        . "FROM ciniki_marketplaces "
        . "LEFT JOIN ciniki_marketplace_sellers ON ("
            . "ciniki_marketplaces.id = ciniki_marketplace_sellers.market_id "
            . "AND ciniki_marketplace_sellers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( isset($args['seller_id']) && $args['seller_id'] != '' && $args['seller_id'] > 0 ) {
        $strsql .= "AND ciniki_marketplace_sellers.id = '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' ";
    }
    $strsql .= ") "
        . "LEFT JOIN ciniki_marketplace_items ON ("
            . "ciniki_marketplace_sellers.id = ciniki_marketplace_items.seller_id "
            . "AND ciniki_marketplace_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_marketplace_sellers.customer_id = ciniki_customers.customer_id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_marketplaces.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_marketplaces.id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
        . "";
    $strsql .= "ORDER BY ciniki_customers.display_name, code, name "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.marketplaces', array(
        array('container'=>'items', 'fname'=>'id', 'name'=>'item',
            'fields'=>array('id', 'display_name', 'code', 'name', 'type', 'category', 
                'price', 'fee_percent', 'sell_date', 'sell_price', 'tenant_fee', 'seller_amount', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['items']) ) {
        $items = array();
    } else {
        $items = $rc['items'];
        foreach($items as $iid => $item) {
            $items[$iid]['item']['fee_percent'] = (float)$item['item']['fee_percent'];
            $items[$iid]['item']['price'] = numfmt_format_currency($intl_currency_fmt, 
                $item['item']['price'], $intl_currency);
            $items[$iid]['item']['sell_price'] = numfmt_format_currency($intl_currency_fmt, 
                $item['item']['sell_price'], $intl_currency);
            $items[$iid]['item']['tenant_fee'] = numfmt_format_currency($intl_currency_fmt, 
                $item['item']['tenant_fee'], $intl_currency);
            $items[$iid]['item']['seller_amount'] = numfmt_format_currency($intl_currency_fmt, 
                $item['item']['seller_amount'], $intl_currency);
        }
    }
    
    return array('stat'=>'ok', 'items'=>$items);
}
?>
