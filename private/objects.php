<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_marketplaces_objects($ciniki) {
    
    $objects = array();
    $objects['market'] = array(
        'name'=>'Market Place',
        'sync'=>'yes',
        'table'=>'ciniki_marketplaces',
        'fields'=>array(
            'name'=>array('name'=>'Market Name'),
            'status'=>array('name'=>'Status'),
            'start_date'=>array('name'=>'Start Date'),
            'end_date'=>array('name'=>'End Date', 'default'=>''),
            ),
        'history_table'=>'ciniki_marketplace_history',
        );
    $objects['seller'] = array(
        'name'=>'Marketplace Seller',
        'sync'=>'yes',
        'table'=>'ciniki_marketplace_sellers',
        'fields'=>array(
            'market_id'=>array('name'=>'Market', 'ref'=>'ciniki.marketplaces.market'),
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'status'=>array('name'=>'Status'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'num_items'=>array('name'=>'Number of items', 'default'=>'0'),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_marketplace_history',
        );
    $objects['item'] = array(
        'name'=>'Marketplace Item',
        'sync'=>'yes',
        'table'=>'ciniki_marketplace_items',
        'fields'=>array(
            'market_id'=>array('name'=>'Market', 'ref'=>'ciniki.marketplaces.market'),
            'seller_id'=>array('name'=>'Seller', 'ref'=>'ciniki.marketplaces.seller'),
            'code'=>array('name'=>'Code'),
            'name'=>array('name'=>'Name'),
            'type'=>array('name'=>'Type'),
            'category'=>array('name'=>'Category', 'default'=>''),
            'price'=>array('name'=>'Price'),
            'fee_percent'=>array('name'=>'Fee'),
            'sell_date'=>array('name'=>'Sell Date', 'default'=>''),
            'sell_price'=>array('name'=>'Sell Price', 'default'=>''),
            'business_fee'=>array('name'=>'Business Fee', 'default'=>''),
            'seller_amount'=>array('name'=>'Seller Amount', 'default'=>''),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_marketplace_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
