# sscloudsearch

## Introduction

Uses AWS php SDK Version 3 and SilverStripe the Fulltextsearch module to add CloudSearch
support to SilverStripe for search.

Copyright (C) 2010-2016 kirk.mayo@solnet.co.nz

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

## Important Info
This is currently still in development and should be CONSIDERED as a prototype or
proof of concept.


## Maintainer Contact

    * Kirk Mayo kirk.mayo (at) solnet (dot) co.nz

## Requirements

    * SilverStripe 3.4 +

## Features

* Use AWS CloudSearch instead of Solr for your search needs

## Composer Installation

  composer require textagroup/sscloudsearch

## Installation Manual

 1. Download the module form GitHub
 2. Extract the file (if you are on windows try 7-zip for extracting tar.gz files
 3. Make sure the folder after being extracted is named 'lazyloadssimages'
 4. Place this directory in your sites root directory. This is the one with framework and cms in it.
 5. Run in your browser - `/dev/build` to rebuild the database.

## Usage ##

You will need to understand the basics of CloudSearch and will need a domain 
setup under CloudSearch.

This will involve setting up relevant access policies under CloudSearch and if you 
want to develop locally you will need to setup a local AWS credentials file on
your machine.

You will need to setup the relevant info to access CloudSearch as below which is
similar to how you would configure Solr.
It is important to set the mode to CloudSearchConfigStore so you can call the 
Solr_Configure dev task to setup the indexes.

```
Solr::configure_server(array(
    'host' => 'YOURCLOUDSEARCHHOST.cloudsearch.amazonaws.com',
    'indexstore' => array(
        'mode' => 'CloudSearchConfigStore',
        'path' => '/solr'
    ),
    'cloudsearch' => array(
        'domainName' => 'YOURCLOUDSEARCHDOMAIN',
        'profile' => 'search',
        'version' => '2013-01-01',
        'region' => 'us-west-2',
    ),
    'cloudsearchdomain' => array(
        'domainName' => 'YOURCLOUDSEARCHDOMAIN',
        'profile' => 'search',
        'version' => '2013-01-01',
        'region' => 'us-west-2',
        'endpoint' => 'http://YOURCLOUDSEARCHHOST.cloudsearch.amazonaws.com/'
    )
));
FulltextSearchable::enable();
```

To setup the search index for CloudSearch you can follow the instructions from the
[fulltextsearch module ](https://github.com/silverstripe/silverstripe-fulltextsearch/blob/master/docs/en/index.md) on creating indexes.

This module has it's own Reindex dev task called CloudSearchReindex which will
load documents into CloudSearch and then index the documents. 
Once the module in installed and the Index has been setup you have setup the 
relevant AWS Identity and Access Management (or AWS credentials) you will
need to run the dev tasks Solr_Configure and CloudSearchReindex.

To query the CloudSearch server via a form overload the standard results method
as per the example below.

```
class Page_Controller extends ContentController {
	public function results($data, $form, $request) {
        $query = new SearchQuery();
        $query->search($request->getVar('Search'));
        $cloudSearch = new CloudSearchQuery();
        $results = $cloudSearch->search($query);
		$data = array(
			'Results' => $results->Matches,
			'Query' => DBField::create_field('Text', $form->getSearchQuery()),
			'Title' => _t('SearchForm.SearchResults', 'Search Results')
		);
		return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
    }
}
```

## TODO ##

* Setup tests
* Add more advanced features like autocomplete, boosting and highligting

