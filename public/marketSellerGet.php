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
// business_id:		The ID of the business the seller is attached to.
// market_id:		The ID of the market to get the details for.
// 
// Returns
// -------
//
function ciniki_marketplaces_marketSellerGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'seller_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Seller'),
		'items'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Items'),
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
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['business_id'], 'ciniki.marketplaces.marketSellerGet'); 
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
	// Get the seller details
	//
	$strsql = "SELECT ciniki_marketplace_sellers.id, "
		. "ciniki_marketplace_sellers.market_id, "
		. "ciniki_marketplace_sellers.customer_id, "
		. "ciniki_marketplace_sellers.status, "
		. "ciniki_marketplace_sellers.status AS status_text, "
		. "ciniki_marketplace_sellers.flags, "
		. "ciniki_marketplace_sellers.flags AS flags_text "
		. "FROM ciniki_marketplace_sellers "
		. "WHERE ciniki_marketplace_sellers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_marketplace_sellers.id = '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.marketplaces', array(
		array('container'=>'sellers', 'fname'=>'id', 'name'=>'seller',
			'fields'=>array('id', 'market_id', 'customer_id', 'status', 'status_text', 'flags', 'flags_text'),
			'maps'=>array('status_text'=>$maps['seller']['status'], 'flags_text'=>$maps['seller']['flags']),
			),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sellers']) || !isset($rc['sellers'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2107', 'msg'=>'Unable to find seller'));
	}
	$seller = $rc['sellers'][0]['seller'];

	//
	// If include customer information
	//
	if( $seller['customer_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
		$rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], array(
			'customer_id'=>$seller['customer_id'], 
			'phones'=>'yes', 
			'emails'=>'yes', 
			'addresses'=>'yes', 
			'subscriptions'=>'no',
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$seller['customer_details'] = $rc['details'];
	}

	//
	// Get the items the seller has
	//
	if( isset($args['items']) && $args['items'] == 'yes' ) {
		//
		// Get the list of market items for a seller
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
			. "AND ciniki_marketplace_items.seller_id = '" . ciniki_core_dbQuote($ciniki, $args['seller_id']) . "' "
			. "ORDER BY code, name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.marketplaces', array(
			array('container'=>'items', 'fname'=>'id', 'name'=>'item',
				'fields'=>array('id', 'code', 'name', 'type', 'category', 
					'price', 'fee_percent', 'sell_date', 'sell_price', 'business_fee', 'seller_amount', 'notes')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['items']) ) {
			$seller['items'] = array();
		} else {
			$seller['items'] = $rc['items'];
			$seller['item_totals'] = array('price'=>0, 'sell_price'=>0, 'business_fee'=>0, 'seller_amount'=>0);
			foreach($seller['items'] as $iid => $item) {
				$seller['items'][$iid]['item']['fee_percent'] = (float)$item['item']['fee_percent'];
				if( $item['item']['price'] != '' ) {
					$seller['items'][$iid]['item']['price'] = numfmt_format_currency($intl_currency_fmt, 
						$item['item']['price'], $intl_currency);
					$seller['item_totals']['price'] = bcadd($seller['item_totals']['price'], $item['item']['price'], 4);
				}
				if( $item['item']['sell_price'] != '' ) {
					$seller['items'][$iid]['item']['sell_price'] = numfmt_format_currency($intl_currency_fmt, 
						$item['item']['sell_price'], $intl_currency);
					$seller['item_totals']['sell_price'] = bcadd($seller['item_totals']['sell_price'], $item['item']['sell_price'], 4);
				}
				if( $item['item']['business_fee'] != '' ) {
					$seller['items'][$iid]['item']['business_fee'] = numfmt_format_currency($intl_currency_fmt, 
						$item['item']['business_fee'], $intl_currency);
					$seller['item_totals']['business_fee'] = bcadd($seller['item_totals']['business_fee'], $item['item']['business_fee'], 4);
				}
				if( $item['item']['seller_amount'] != '' ) {
					$seller['items'][$iid]['item']['seller_amount'] = numfmt_format_currency($intl_currency_fmt, 
						$item['item']['seller_amount'], $intl_currency);
					$seller['item_totals']['seller_amount'] = bcadd($seller['item_totals']['seller_amount'], $item['item']['seller_amount'], 4);
				}
			}
			$seller['item_totals']['price'] = numfmt_format_currency($intl_currency_fmt,
				$seller['item_totals']['price'], $intl_currency);
			$seller['item_totals']['sell_price'] = numfmt_format_currency($intl_currency_fmt,
				$seller['item_totals']['sell_price'], $intl_currency);
			$seller['item_totals']['business_fee'] = numfmt_format_currency($intl_currency_fmt,
				$seller['item_totals']['business_fee'], $intl_currency);
			$seller['item_totals']['seller_amount'] = numfmt_format_currency($intl_currency_fmt,
				$seller['item_totals']['seller_amount'], $intl_currency);
		}
	}

	return array('stat'=>'ok', 'seller'=>$seller);
}
?>
