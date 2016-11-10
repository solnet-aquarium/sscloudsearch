<?php

require '../vendor/autoload.php';

use Aws\CloudSearch\CloudSearchClient;

/**
 * Class CloudStoreConfigStoreCloudSearch
 *
 * Config store for AWS CloudSearch
 */
class CloudSearchConfigStore implements SolrConfigStore
{
    public function __construct($config)
    {
        $options = Solr::solr_options();

        $this->client = new CloudSearchClient($options['cloudsearch']);

        $this->domainName = $options['cloudsearch']['domainName'];
    }

    public function uploadFile($index, $file)
    {
    }

    public function uploadString($index, $filename, $string)
    {
        $indexObj = new $index();
        $indexFields = $indexObj->getFulltextFields();
        $fieldTypes = array(
            'Varchar' => 'text',
            'Int' => 'int',
            'Datetime' => 'date'
        );
        $processedIndexes = 0;
        foreach ($indexFields as $indexField) {
            $definedField = array(
                'DomainName' => $this->domainName,
                'IndexField' => [
                    'IndexFieldName' => strtolower($indexField['name']),
                    'IndexFieldType' => isset($fieldTypes[$indexField['type']])
                        ? $fieldTypes[$indexField['type']] : 'text'
                ]
            );
            try {
                $result = $this->client->defineIndexField($definedField);
            } catch (Exception $e) {
                echo 'DefinedIndexField Exception: ', $e->getMessage(), "\n";
            }
            if (isset($result) && $result instanceof Aws\Result) {
                $metadata = $result->get('@metadata');
                if (isset($metadata['statusCode']) && $metadata['statusCode'] == 200) {
                    $processedIndexes++;
                }
            }
        }
        echo $processedIndexes . " indexes have been created\n";
        $this->indexDocuments();
    }

    public function indexDocuments()
    {
        $result = $this->client->indexDocuments([
            'DomainName' => $this->domainName
        ]);
        // stop processing by the fulltextsearch module as Solr is not being used directly
        exit;
    }

    public function instanceDir($index)
    {
        return null;
    }
}
