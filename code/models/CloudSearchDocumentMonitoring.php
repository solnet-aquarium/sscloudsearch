<?php

/**
 * Keep track of the state of CloudSearch Documents
 * as they may need to be removed
 *
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 */

class CloudSearchDocumentMonitoring extends DataObject
{
    private static $db = array(
        'DocumentID' => 'Varchar',
        'Status' => 'Enum("Updating, Add, Delete", "Updating")'
    );
}
