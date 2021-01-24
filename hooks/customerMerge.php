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
function ciniki_marketplaces_hooks_customerMerge($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    if( !isset($args['primary_customer_id']) || $args['primary_customer_id'] == '' 
        || !isset($args['secondary_customer_id']) || $args['secondary_customer_id'] == '' ) {
        return array('stat'=>'ok');
    }

    //
    // Keep track of how many items we've updated
    //
    $updated = 0;

    //
    // Check if primary customer already exists as a seller
    //
    $strsql = "SELECT id, customer_id, status "
        . "FROM ciniki_marketplace_sellers "
        . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['primary_customer_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'seller');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.21', 'msg'=>'Unable to find sellers', 'err'=>$rc['err']));
    }
    if( isset($rc['rows'][0]['id']) ) {
        $primary_seller_id = $rc['rows'][0]['id'];
        //
        // Get the secondary customer seller
        //
        $strsql = "SELECT id, uuid "
            . "FROM ciniki_marketplace_sellers "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'seller');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.17', 'msg'=>'Unable to load seller', 'err'=>$rc['err']));
        }
        $sellers = isset($rc['rows']) ? $rc['rows'] : array();
        foreach($sellers as $seller) {
            //
            // Get the list of items
            //
            $strsql = "SELECT id "
                . "FROM ciniki_marketplace_items "
                . "WHERE ciniki_marketplace_items.seller_id = '" . ciniki_core_dbQuote($ciniki, $seller['id']) . "' "
                . "AND ciniki_marketplace_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
            $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.marketplaces', 'items', 'id');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.18', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
            }
            $items = isset($rc['items']) ? $rc['items'] : array();

            //
            // Update the items
            //
            foreach($items as $item) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.marketplaces.item', $item, array(
                    'seller_id' => $primary_seller_id,
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.19', 'msg'=>'Unable to update the item'));
                }
            }

            //
            // Delete secondary seller
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.marketplaces.seller', $seller['id'], $seller['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.22', 'msg'=>'Unable to remove seller from marketplaces', 'err'=>$rc['err']));
            }
        }
    } 

    //
    // Primary customer doesn't exist, update seller record
    //
    else {
        $strsql = "SELECT id "
            . "FROM ciniki_marketplace_sellers "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.marketplaces', 'items');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.20', 'msg'=>'Unable to find sellers', 'err'=>$rc['err']));
        }
        $items = $rc['rows'];
        foreach($items as $i => $row) {
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.marketplaces.seller', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.16', 'msg'=>'Unable to update exhibitors.', 'err'=>$rc['err']));
            }
            $updated++;
        }
    }

    //
    // Check if anything updated
    //
    if( $updated > 0 ) {
        //
        // Update the last_change date in the tenant modules
        // Ignore the result, as we don't want to stop user updates if this fails.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'marketplaces');
    }

    return array('stat'=>'ok', 'updated'=>$updated);
}
?>
