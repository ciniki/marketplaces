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
// tnid:     The ID of the tenant the seller is attached to.
// market_id:       The ID of the market to get the details for.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'market_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Market'),
        'output'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Format'),
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
    $rc = ciniki_marketplaces_checkAccess($ciniki, $args['tnid'], 'ciniki.marketplaces.marketInventory'); 
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.marketplaces.7', 'msg'=>'Market does not exist'));
    }
    $market_name = $rc['market']['name'];

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
        . "sell_price, tenant_fee, seller_amount, "
        . "ciniki_marketplace_items.notes "
        . "FROM ciniki_marketplace_items "
        . "LEFT JOIN ciniki_marketplace_sellers ON ("
            . "ciniki_marketplace_items.seller_id = ciniki_marketplace_sellers.id "
            . "AND ciniki_marketplace_sellers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_marketplace_sellers.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_marketplace_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_marketplace_items.market_id = '" . ciniki_core_dbQuote($ciniki, $args['market_id']) . "' "
        . "ORDER BY code, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.marketplaces', array(
        array('container'=>'items', 'fname'=>'id',
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
    }

    //
    // Start an excel file
    //
    ini_set('memory_limit', '4192M');
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->setActiveSheetIndex(0);
    $sheet->setTitle(substr($market_name, 0, 31));

    //
    // Headers
    //
    $i = 0;
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Code', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Customer', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Type', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Name', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Price', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Fee %', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Date', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Sell Price', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Fees', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Amount', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Notes', false);

    $sheet->getStyle('A1')->getFont()->setBold(true);
    $sheet->getStyle('B1')->getFont()->setBold(true);
    $sheet->getStyle('C1')->getFont()->setBold(true);
    $sheet->getStyle('D1')->getFont()->setBold(true);
    $sheet->getStyle('E1')->getFont()->setBold(true);
    $sheet->getStyle('F1')->getFont()->setBold(true);
    $sheet->getStyle('G1')->getFont()->setBold(true);
    $sheet->getStyle('H1')->getFont()->setBold(true);
    $sheet->getStyle('I1')->getFont()->setBold(true);
    $sheet->getStyle('J1')->getFont()->setBold(true);
    $sheet->getStyle('K1')->getFont()->setBold(true);

    $row = 2;
    foreach($items as $item) {
//      print_r($item); exit;
        $i = 0;
        $sheet->setCellValueByColumnAndRow($i++, $row, $item['code']);
        $sheet->setCellValueByColumnAndRow($i++, $row, $item['display_name']);
        $sheet->setCellValueByColumnAndRow($i++, $row, $item['type']);
        $sheet->setCellValueByColumnAndRow($i++, $row, $item['name']);
        $sheet->setCellValueByColumnAndRow($i++, $row, $item['price']);
        if( $item['fee_percent'] > 0 ) {
            $sheet->setCellValueByColumnAndRow($i++, $row, ($item['fee_percent']/100));
        } else {
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['fee_percent']);
        }
        if( $item['sell_date'] != '' && $item['sell_date'] != '0' ) {
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['sell_date']);
        } else {
            $sheet->setCellValueByColumnAndRow($i++, $row, '');
        }
        if( $item['sell_price'] != '' && $item['sell_price'] != 0 ) {
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['sell_price']);
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['tenant_fee']);
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['seller_amount']);
        } else {
            $sheet->setCellValueByColumnAndRow($i++, $row, '');
            $sheet->setCellValueByColumnAndRow($i++, $row, '');
            $sheet->setCellValueByColumnAndRow($i++, $row, '');
        }

        $row++;
    }
    $sheet->getStyle('E2:E' . ($row-1))->getNumberFormat()->setFormatCode("$#,##0.00");
    $sheet->getStyle('F2:F' . ($row-1))->getNumberFormat()->setFormatCode("0%");
    $sheet->getStyle('H2:H' . ($row-1))->getNumberFormat()->setFormatCode("$#,##0.00");
    $sheet->getStyle('I2:I' . ($row-1))->getNumberFormat()->setFormatCode("$#,##0.00");
    $sheet->getStyle('J2:J' . ($row-1))->getNumberFormat()->setFormatCode("$#,##0.00");

    PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setAutoSize(true);
    $sheet->getColumnDimension('E')->setAutoSize(true);
    $sheet->getColumnDimension('F')->setAutoSize(true);
    $sheet->getColumnDimension('G')->setAutoSize(true);
    $sheet->getColumnDimension('H')->setAutoSize(true);
    $sheet->getColumnDimension('I')->setAutoSize(true);
    $sheet->getColumnDimension('J')->setAutoSize(true);

    //
    // Output the excel
    //
    header('Content-Type: application/vnd.ms-excel');
    $filename = preg_replace('/[^a-zA-Z0-9\-]/', '', $market_name);
    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    return array('stat'=>'exit');
}
?>
