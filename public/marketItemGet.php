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
// business_id:		The ID of the business the item is attached to.
// market_id:		The ID of the market to get the details for.
// 
// Returns
// -------
//
function ciniki_marketplaces_marketItemGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Item'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'checkAccess');
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['business_id'], 'ciniki.marketplaces.marketItemGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Load the business intl settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
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
	// Get the item details
	//
	$strsql = "SELECT ciniki_marketplace_items.id, "
		. "ciniki_marketplace_items.code, "
		. "ciniki_marketplace_items.name, "
		. "ciniki_marketplace_items.type, "
		. "ciniki_marketplace_items.category, "
		. "ciniki_marketplace_items.price, "
		. "ciniki_marketplace_items.fee_percent, "
		. "DATE_FORMAT(ciniki_marketplace_items.sell_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sell_date, "
		. "IF(ciniki_marketplace_items.sell_price=0, '', ciniki_marketplace_items.sell_price) AS sell_price, "
		. "IF(ciniki_marketplace_items.business_fee=0, '', ciniki_marketplace_items.business_fee) AS business_fee, "
		. "IF(ciniki_marketplace_items.seller_amount=0, '', ciniki_marketplace_items.seller_amount) AS seller_amount, "
		. "ciniki_marketplace_items.notes "
		. "FROM ciniki_marketplace_items "
		. "WHERE ciniki_marketplace_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_marketplace_items.id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.marketplaces', array(
		array('container'=>'items', 'fname'=>'id', 'name'=>'item',
			'fields'=>array('id', 'code', 'name', 'type', 'category', 
				'price', 'fee_percent', 'sell_date', 'sell_price', 'business_fee', 'seller_amount', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['items']) || !isset($rc['items'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2111', 'msg'=>'Unable to find item'));
	}
	$item = $rc['items'][0]['item'];

	if( $item['fee_percent'] != '' ) {
		$item['fee_percent'] = (float)$item['fee_percent'];
	}
	if( $item['price'] != '' ) {
		$item['price'] = numfmt_format_currency($intl_currency_fmt, $item['price'], $intl_currency);
	}
	if( $item['sell_price'] != '' ) {
		$item['sell_price'] = numfmt_format_currency($intl_currency_fmt, $item['sell_price'], $intl_currency);
	}
	if( $item['business_fee'] != '' ) {
		$item['business_fee'] = numfmt_format_currency($intl_currency_fmt, $item['business_fee'], $intl_currency);
	}
	if( $item['seller_amount'] != '' ) {
		$item['seller_amount'] = numfmt_format_currency($intl_currency_fmt, $item['seller_amount'], $intl_currency);
	}

	return array('stat'=>'ok', 'item'=>$item);
}
?>
