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
function ciniki_marketplaces_flags($ciniki, $modules) {
    $flags = array(
        array('flag'=>array('bit'=>'1', 'name'=>'Fees')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
