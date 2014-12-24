<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_marketplaces_maps($ciniki, $modules) {
	$maps = array();
	$maps['market'] = array(
		'status'=>array(
			'10'=>'Active',
			'50'=>'Archived',
			),
		);
	$maps['seller'] = array(
		'status'=>array(
			'10'=>'Applied',
			'20'=>'Accepted',
			),
		'flags'=>array(
			0=>'',
			0x01=>'Fee Paid',
			),
		);

	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
