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
			'name'=>array(),
			'status'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			),
		'history_table'=>'ciniki_marketplace_history',
		);
	$objects['seller'] = array(
		'name'=>'Marketplace Seller',
		'sync'=>'yes',
		'table'=>'ciniki_marketplace_sellers',
		'fields'=>array(
			'market_id'=>array('ref'=>'ciniki.marketplaces.market'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'status'=>array(),
			'flags'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_marketplace_history',
		);
	$objects['item'] = array(
		'name'=>'Marketplace Item',
		'sync'=>'yes',
		'table'=>'ciniki_marketplace_items',
		'fields'=>array(
			'market_id'=>array('ref'=>'ciniki.marketplaces.market'),
			'seller_id'=>array('ref'=>'ciniki.marketplaces.seller'),
			'code'=>array(),
			'name'=>array(),
			'type'=>array(),
			'category'=>array(),
			'price'=>array(),
			'fee_percent'=>array(),
			'sell_date'=>array(),
			'sell_price'=>array(),
			'business_fee'=>array(),
			'seller_amount'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_marketplace_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
