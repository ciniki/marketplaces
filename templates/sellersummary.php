<?php
//
// Description
// ===========
// This function returns a PDF of the price list for a market.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_marketplaces_templates_sellersummary(&$ciniki, $tnid, $args) {

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $title = '';
        public $footer_msg = '';

        public function Header() {
            //
            // Output the title
            //
            $this->SetFont('', 'B', 14);
            $this->Cell(180, 12, $this->title, 0, false, 'C', 0);
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            if( isset($this->footer_msg) && $this->footer_msg != '' ) {
                $this->Cell(90, 10, $this->footer_msg,
                    0, false, 'L', 0, '', 0, false, 'T', 'M');
                $this->Cell(90, 10, 'Page ' . $this->getPageNumGroupAlias().'/'.$this->getPageGroupAlias(), 
                    0, false, 'R', 0, '', 0, false, 'T', 'M');
            } else {
                // Center the page number if no footer message.
                $this->Cell(0, 10, 'Page ' . $this->getPageNumGroupAlias().'/'.$this->getPageGroupAlias(), 
                    0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    if( isset($args['title']) ) {
        $pdf->title = $args['title'];
    }
    if( isset($args['footer']) ) {
        $pdf->footer_msg = $args['footer'];
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($args['author']);
    $pdf->SetTitle($pdf->title);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, 19, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    $num_seller = 0;
    foreach($args['sellers'] as $seller) {
        if( !isset($seller['items']) || count($seller['items']) == 0 ) {
            continue;
        }
        // set font
        $pdf->SetFont('times', 'BI', 10);
        $pdf->SetCellPadding(2);

        $pdf->title = $args['title'] . ' - ' . $seller['display_name'];
        // add a page
        $pdf->startPageGroup();
        $pdf->AddPage();
        $pdf->SetFillColor(255);
        $pdf->SetTextColor(0);
        $pdf->SetDrawColor(51);
        $pdf->SetLineWidth(0.15);
        //
        // Add the items
        //
        $w = array(25, 80, 25, 25, 25);
        $pdf->SetFillColor(224);
        $pdf->SetFont('', 'B');
        $pdf->SetCellPadding(2);
        $pdf->Cell($w[0], 6, 'Code', 1, 0, 'C', 1);
        $pdf->Cell($w[1], 6, 'Item', 1, 0, 'C', 1);
        $pdf->Cell($w[2], 6, 'Price', 1, 0, 'C', 1);
        $pdf->Cell($w[3], 6, 'Fees', 1, 0, 'C', 1);
        $pdf->Cell($w[4], 6, 'Amount', 1, 0, 'C', 1);
        $pdf->Ln();
        $pdf->SetFillColor(236);
        $pdf->SetTextColor(0);
        $pdf->SetFont('');

        $total_sell_price = 0;
        $total_tenant_fee = 0;
        $total_seller_amount = 0;
        $num_items = count($seller['items']);
        $num = 0;
        $fill = 0;
        foreach($seller['items'] as $item) {
            $lh = 6;
            $code = $item['code'];
            $name = (($item['type']!=null&&$item['type']!='')?$item['type'] . ' - ':'') . $item['name'];
            $total_sell_price = bcadd($total_sell_price, $item['sell_price'], 4);
            $total_tenant_fee = bcadd($total_tenant_fee, $item['tenant_fee'], 4);
            $total_seller_amount = bcadd($total_seller_amount, $item['seller_amount'], 4);
            $sell_price = numfmt_format_currency($intl_currency_fmt, $item['sell_price'], $intl_currency);
            $tenant_fee = numfmt_format_currency($intl_currency_fmt, $item['tenant_fee'], $intl_currency);
            $seller_amount = numfmt_format_currency($intl_currency_fmt, $item['seller_amount'], $intl_currency);
            $nlines = $pdf->getNumLines($name, $w[1]);
            if( $nlines == 2 ) {
                $lh = 3+($nlines*5);
            } elseif( $nlines > 2 ) {
                $lh = 2+($nlines*5);
            }
            // Check if we need a page break

            $num_left = $num_items - $num;
            // If there is only 1 row left, then make sure there is enough room for the totals.
            if( $pdf->getY() > ($pdf->getPageHeight() - ($num_left>1?(20+($nlines*15)):60)) ) {
                $pdf->AddPage();
                $pdf->SetFillColor(224);
                $pdf->SetFont('', 'B');
                $pdf->Cell($w[0], 6, 'Code', 1, 0, 'C', 1);
                $pdf->Cell($w[1], 6, 'Item', 1, 0, 'C', 1);
                $pdf->Cell($w[2], 6, 'Price', 1, 0, 'C', 1);
                $pdf->Cell($w[3], 6, 'Fees', 1, 0, 'C', 1);
                $pdf->Cell($w[4], 6, 'Amount', 1, 0, 'C', 1);
                $pdf->Ln();
                $fill = 0;
                $pdf->SetFillColor(236);
                $pdf->SetTextColor(0);
                $pdf->SetFont('');
            }
            $pdf->MultiCell($w[0], $lh, $code, 1, 'L', $fill, 
                0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->MultiCell($w[1], $lh, $name, 1, 'L', $fill, 
                0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->MultiCell($w[2], $lh, $sell_price, 1, 'R', $fill, 
                0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->MultiCell($w[3], $lh, $tenant_fee, 1, 'R', $fill, 
                0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->MultiCell($w[4], $lh, $seller_amount, 1, 'R', $fill, 
                0, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Ln(); 
            $fill=!$fill;
            $num++;
        }

        //
        // Add totals
        //
        $total_sell_price = numfmt_format_currency($intl_currency_fmt, $total_sell_price, $intl_currency);
        $total_tenant_fee = numfmt_format_currency($intl_currency_fmt, $total_tenant_fee, $intl_currency);
        $total_seller_amount = numfmt_format_currency($intl_currency_fmt, $total_seller_amount, $intl_currency);
        $pdf->SetFillColor(224);
        $lh = 6;
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0] + $w[1], $lh, 'Totals', 1, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, $total_sell_price, 1, 0, 'R', 1);
        $pdf->Cell($w[3], $lh, $total_tenant_fee, 1, 0, 'R', 1);
        $pdf->Cell($w[4], $lh, $total_seller_amount, 1, 0, 'R', 1);
        $num_seller++;
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
