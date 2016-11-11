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

    public $fieldTypes = array(
        'Varchar' => 'text',
        'Int' => 'int',
        'SS_Datetime' => 'date',
        'Date' => 'date',
        'Boolean' => 'int',
        'Currency' => 'double',
        'Decimal' => 'double',
        'Enum' => 'text',
        'HTMLText' => 'text',
        'HTMLVarchar' => 'text',
        'Percentage' => 'double',
        'Text' => 'text',
        'Time' => 'date'
    );

    public $processedIndexes;
    public $processedSorts;

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
        $this->processedIndexes = 0;
        $this->processedSorts = 0;
        foreach ($indexFields as $indexField) {
            $this->defineIndexField($indexField);
            $this->defineSuggester($indexField);
        }
        echo "Indexes created: {$this->processedIndexes}\n";
        echo "Sort expressions created: {$this->processedSorts}\n";
        $this->indexDocuments();
    }

    /**
     * Method to define an index field on a AWS CloudSearch domain
     * @param array $indexField Array containg details of index field to setup
     */
    public function defineIndexField($indexField) {
        $definedField = array(
            'DomainName' => $this->domainName,
            'IndexField' => [
                'IndexFieldName' => strtolower($indexField['name']),
                'IndexFieldType' => isset($this->fieldTypes[$indexField['type']])
                    ? $this->fieldTypes[$indexField['type']] : 'text'
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
                $this->processedIndexes++;
            }
        }
    }

    /**
     * Method to set up a suggester on a AWS CloudSearch domain
     * @param array $indexField Array containg details of seggesters to set up
     */
    public function defineSuggester($indexField) {
        // check if we need to add any suggesters for this field
        $extraOptions = (isset($indexField['extra_options']))
                ? $indexField['extra_options'] : null;
        if (isset($extraOptions['suggesters']) && is_array($extraOptions['suggesters'])) {
            foreach ($extraOptions['suggesters'] as $suggester) {
                $fuzzyMatching = (isset($suggester['FuzzyMatching']))
                    ? $suggester['FuzzyMatching'] : 'none';
                $sortExpression = (isset($suggester['SortExpression']))
                    ? $suggester['SortExpression'] : null;
                $defineSuggester = array(
                    'DomainName' => $this->domainName,
                    'Suggester' => array(
                        'SuggesterName' => strtolower("suggester_{$indexField['name']}"),
                        'DocumentSuggesterOptions' => array(
                            'SourceField' => strtolower($indexField['name']),
                            'FuzzyMatching' => $fuzzyMatching,
                        )
                    )
                );
                if ($sortExpression) {
                    $defineSuggester['Suggester']['DocumentSuggesterOptions']['SortExpression']
                        = $sortExpression;
                }
                try {
                    $result = $this->client->defineSuggester($defineSuggester);
                } catch (Exception $e) {
                    echo 'DefineSuggestor Exception: ', $e->getMessage(), "\n";
                }
                if (isset($result) && $result instanceof Aws\Result) {
                    $this->processedSorts++;
                }
             }
        }
    }

    /**
     * Method to request the indexing of documents on a AWS CloudSearch domain
     */
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
