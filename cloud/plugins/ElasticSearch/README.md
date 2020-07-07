# How to setup locally

-   Make sure you are running the local host queue. (See `queue-stack-dev` repo).
-   Add the following to your config.

```php
$Configuration['ElasticDev.AccountID'] = 5000;
$Configuration['ElasticDev.SiteID'] = 5000;
$Configuration['ElasticDev.Secret'] = GET_THE_SECRET_FROM_1PASSWORD;
```

This will automatically configure the `DevElasticHttpConfig`.

For more control over the implementation, implement the `AbstractElasticHttpConfig` class and add the following somewhere in the container initialization.

```php
use Vanilla\Cloud\ElasticSearch\Http\AbstractElasticHttpConfig;

$container
    ->rule(AbstractElasticHttpConfig::class)
    ->setClass(CustomElasticHttpConfig::class);
```

# How to use

Make sure you have the client configured, and the host queue running.

https://staff.vanillaforums.com/kb/articles/255-dev-setup-overview

## Indexing Your Site

https://staff.vanillaforums.com/kb/articles/253-index-your-local-site

The following API endpoint will trigger a full content index.

```
POST /api/v2/resources/crawl-elastic
```

## Running queries

```php
<?php
use Vanilla\Cloud\ElasticSearch\Http\AbstractElasticHttpClient;

/** @var AbstractElasticHttpClient $searchApi */
$searchApi = $dic->get(AbstractElasticHttpClient::class);

// Search for stuff in discussion/comment index.
$elasticsearchResult = $searchApi->search(
    [
        'discussions',
        'comements',
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
