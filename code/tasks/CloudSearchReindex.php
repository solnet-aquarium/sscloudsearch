<?php

require '../vendor/autoload.php';

use Aws\CloudSearchDomain\CloudSearchDomainClient;

/**
 * Class CloudSearchReindex
 *
 * Class to manage indexes and setup a a document reindex request
 */
class CloudSearchReindex extends Solr_Reindex
{
	protected $title = "CloudSearch Reindexing";

	protected $description = "Reindexing of documents on a CloudSearch domain";

    public function doReindex($request)
    {
        $indexInfo = array();
        $options = Solr::solr_options();
        $this->client = new CloudSearchDomainClient($options['cloudsearchdomain']);
        foreach(ClassInfo::subclassesFor('SolrIndex') as $solrIndexClass) {
            if ($solrIndexClass != 'SolrIndex') {
                $data = array(
                    'type' => 'add'
                );
                $indexObj = new $solrIndexClass();
                $indexFields = $indexObj->getFulltextFields();
                foreach($indexFields as $field) {
                    if (isset($field['class']) && isset($field['field'])) {
                        $indexInfo[$field['class']][] = $field['field'];
                    }
                }
            }
        }
        // set the document monitoring status to Updating
        $update = SQLUpdate::create('CloudSearchDocumentMonitoring')
            ->addWhere(array('Status' => 'Add'))
            ->assign('Status', 'Updating');
        $update->execute();
        $documents = array();
        foreach ($indexInfo as $class => $fields) {
            $records = $class::get();
            foreach ($records as $record) {
                $id = $class.$record->ID;
                $monitoring = CloudSearchDocumentMonitoring::get()
                    ->filter('DocumentID', $id)->first();
                if (!$monitoring || !$monitoring->exists()) {
                    $monitoring = CloudSearchDocumentMonitoring::create();
                    $monitoring->DocumentID = $id;
                }
                $monitoring->Status = 'Add';
                $monitoring->write();
                $data = array(
                    'id' => $id,
                    'type' => 'add'
                );
                foreach ($fields as $field) {
                    $data['fields'][strtolower("{$class}_$field")] = $record->$field;
                }
                $documents[] = $data;
            }
        }
	// any rows which are still set to updating can be deleted from CloudSearch
        $deleteDocuments = CloudSearchDocumentMonitoring::get()
            ->filter('Status', 'Updating');
        foreach ($deleteDocuments as $delete) {
            $delete->Status = 'Delete';
            $delete->write();
            $data = array(
                'id' => $delete->DocumentID,
                'type' => 'delete'
            );
            $documents[] = $data;
        }
        $jsonDocuments = json_encode($documents);
        $result = null;
        try {
            $result = $this->client->uploadDocuments([
                'contentType' => 'application/json',
                'documents' => $jsonDocuments
            ]);
        } catch (Exception $e) {
            echo 'UploadDocuments Exception: ', $e->getMessage(), "\n";
        }
        if (isset($result) && $result instanceof Aws\Result) {
            echo $result->get('adds') . " entries added\n";
            echo $result->get('deletes') . " entries deleted\n";
        }
    }
}
