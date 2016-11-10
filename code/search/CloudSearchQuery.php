<?php

require '../vendor/autoload.php';

use Aws\CloudSearchDomain\CloudSearchDomainClient;

/**
 * Class CloudSearchQuery
 *
 * Handle sending off a search query to AWS CloudSearch and
 * then formatting the result to be output in a paginated list
 */
class CloudSearchQuery
{
    public function __construct()
    {
        $options = Solr::solr_options();

        $this->client = new CloudSearchDomainClient($options['cloudsearchdomain']);

        $this->domainName = $options['cloudsearch']['domainName'];
    }

    public function search(SearchQuery $query, $offset = -1, $limit = -1, $params = array())
    {
        $hlq = array(); // Highlight query

        // Build the search itself
        foreach ($query->search as $search) {
            $text = $search['text'];
            preg_match_all('/"[^"]*"|\S+/', $text, $parts);
        }
        $payload = array(
            //'cursor' => '<string>',
            //'expr' => '<string>',
            //'facet' => '<string>',
            //'filterQuery' => '<string>',
            //'highlight' => '<string>',
            //'partial' => true || false,
            'query' => $text, // REQUIRED
            //'queryOptions' => '<string>',
            //'queryParser' => 'simple|structured|lucene|dismax',
            //'return' => '<string>',
            //'size' => <integer>,
            //'sort' => '<string>',
            //'start' => <integer>,
            //'stats' => '<string>',
        );

        try {
            $searchResult = $this->client->search($payload);
        } catch (Exception $e) {
            echo 'CloudSearchIndex Exception: ', $e->getMessage(), "\n";
        }
        $hits = $searchResult->get('hits');
        if ($hits && isset($hits['hit'])) {
            $results = new ArrayList();
            $numFound = (isset($hits['found'])) ? $hits['found'] : 0;
            foreach ($hits['hit'] as $hit) {
    
                // need to find a better way of get class and ID
                // as this could break if the table name has a number in it
                $db = preg_split('/(?<=[a-z])(?=[0-9])/i', $hit['id']);
                if (count($db) == 2) {
                    $result = DataObject::get_by_id($db[0], $db[1]);
                    if ($result) {
                        $results->push($result);
                    }
                }
            }
            $ret = array();
            $ret['Matches'] = new PaginatedList($results);
            $ret['Matches']->setLimitItems(false);
            // Tell PaginatedList how many results there are
            $ret['Matches']->setTotalItems($numFound);
            // Results for current page start at $offset
            $ret['Matches']->setPageStart($offset);
            // Results per page
            $ret['Matches']->setPageLength($limit);
            return new ArrayData($ret);
        }
    }
}
