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
function ciniki_marketplaces_sync_objects($ciniki, &$sync, $tnid, $args) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'marketplaces', 'private', 'objects');
    return ciniki_marketplaces_objects($ciniki);
}
?>
