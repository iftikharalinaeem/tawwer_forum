# How to setup locally

Add the following code to `conf/bootstrap.after.php` and replace the parts that begins with `{{` and end with  `}}`.
```php
<?php

use Vanilla\Inf\Search\AbstractSearchApiInformationProvider;

class DevSearchApiInformationProvider extends AbstractSearchApiInformationProvider
{
    public function getBaseUrl(): string
    {
        return 'https://ms-vanilla-search-api-dev.v-fabric.net/api/v1.0/';
    }

    protected function getSecret(): string
    {
        // Replace this. Look for the secret in 1Password
        return '{{DEV_SEARCH_API_SECRET}}';
    }

    protected function getTokenPayload(): array
    {
        return [
            'accountId' => {{ACCOUNT_ID}},
            'siteId' => {{SITE_ID}},
        ];
    }
}

$dic->rule(AbstractSearchApiInformationProvider::class)
    ->setClass(DevSearchApiInformationProvider::class)
;
```

# How to use

```php
<?php
use Vanilla\Inf\Search\SearchApi;

/** @var SearchApi $searchApi */
$searchApi = $dic->get(SearchApi::class);

// Search for stuff in discussion/comment index.
$elasticsearchResult = $searchApi->search(
    [
        SearchApi::INDEX_ALIAS_DISCUSSION,
        SearchApi::INDEX_ALIAS_COMMENT,
    ],
    // See https://www.elastic.co/guide/en/elasticsearch/reference/7.8/query-dsl-terms-query.html#query-dsl-terms-lookup-example
    [
        'query' => [
            'terms' => [
                'body' => [
                    'index' => SearchApi::INDEX_ALIAS_DISCUSSION,
                    'id' => 1,
                    'path' => 'body.keyword',
                ],
            ],             
        ],       
    ]
);
```
