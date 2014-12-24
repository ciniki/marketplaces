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
function ciniki_marketplaces_marketInventory($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'market_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market'),
		'output'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Format'),
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
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['business_id'], 'ciniki.marketplaces.marketInventory'); 
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
		// Get the list of market items for a seller
		//
		$strsql = "SELECT ciniki_marketplace_items.id, "
			. "ciniki_marketplace_items.code, "
			. "ciniki_customers.display_name, "
			. "ciniki_marketplace_items.name, "
			. "ciniki_marketplace_items.type, "
			. "ciniki_marketplace_items.category, "
			. "ciniki_marketplace_items.price, "
			. "ciniki_marketplace_items.fee_percent, "
			. "DATE_FORMAT(ciniki_marketplace_items.sell_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS sell_date, "
			. "sell_price, business_fee, seller_amount, "
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
				}
				if( $item['item']['sell_price'] != '' ) {
					$seller['items'][$iid]['item']['sell_price'] = numfmt_format_currency($intl_currency_fmt, 
						$item['item']['sell_price'], $intl_currency);
				}
				if( $item['item']['business_fee'] != '' ) {
					$seller['items'][$iid]['item']['business_fee'] = numfmt_format_currency($intl_currency_fmt, 
						$item['item']['business_fee'], $intl_currency);
				}
				if( $item['item']['seller_amount'] != '' ) {
					$seller['items'][$iid]['item']['seller_amount'] = numfmt_format_currency($intl_currency_fmt, 
						$item['item']['seller_amount'], $intl_currency);
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
