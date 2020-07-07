<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Sphinx\Search;

use Vanilla\Adapters\SphinxClient;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Vanilla\Search\AbstractSearchDriver;
use Vanilla\Search\SearchOptions;
use Vanilla\Search\SearchResults;

/**
 * Search driver.
 */
class SphinxSearchDriver extends AbstractSearchDriver {

    const MAX_RESULTS = 1000;

    /** @var string */
    private $sphinxServer;

    /** @var int */
    private $sphinxPort;

    /** @var string */
    private $databaseName;

    /** @var SearchRecordTypeProviderInterface */
    private $sphinxDTypeRecordProvider;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     * @param SearchRecordTypeProviderInterface $searchRecordProvider
     */
    public function __construct(ConfigurationInterface $config, SearchRecordTypeProviderInterface $searchRecordProvider) {
        $this->sphinxServer = $config->get('Plugins.Sphinx.Server', $config->get('Database.Host', 'localhost'));
        $this->sphinxPort = $config->get('Plugins.Sphinx.Port', 9312);
        $this->databaseName = str_replace(['-'], '_', c('Database.Name')) . '_';
        $this->sphinxDTypeRecordProvider = $searchRecordProvider;
    }

    /**
     * Perform a search.
     *
     * @param array $queryData The query to search for.
     * @param SearchOptions $options Options for the query.
     *
     * @return SearchResults
     */
    public function search(array $queryData, SearchOptions $options): SearchResults {
        $sphinxClient = $this->getSphinxClient();
        $query = new SphinxSearchQuery($sphinxClient, $this->getSearchTypes(), $queryData);

        $sphinxClient->setLimits($options->getOffset(), $options->getLimit(), self::MAX_RESULTS);

        $indexes = $this->getIndexNames($query);

        $this->applyDtypes($sphinxClient, $query);

        $search = $sphinxClient->query($query->getQuery(), implode(' ', $indexes));
        if (!is_array($search)) {
            $error = $sphinxClient->getLastError();
            throw new SphinxSearchException($error);
        }

        $records = $this->extractRecordsFromSphinxResult($search);
        $results = $this->convertRecordsToResultItems($records);

        $total = $search['total'] ?? 0;

        return new SearchResults(
            $results,
            $total,
            $options->getOffset(),
            $options->getLimit()
        );
    }

    /**
     * Get index names.
     *
     * @param SphinxSearchQuery $query
     *
     * @return string[]
     */
    public function getIndexNames(SphinxSearchQuery $query): array {
        $prefix = str_replace(['-'], '_', c('Database.Name')) . '_';
        $result = [];

        $types = $query->getQueryParameter('recordTypes', null);

        /** @var SearchRecordTypeInterface $recordType */
        foreach ($this->sphinxDTypeRecordProvider->getAll() as $recordType) {
            $indexName = $recordType->getIndexName();

            if ($types !== null && !in_array($recordType->getApiTypeKey(), $types)) {
                continue;
            }

            $result[] = $prefix . $indexName;
        }

        return $result;
    }

    /**
     * Apply dtypes filters to search query.
     *
     * @param SphinxClient $sphinxClient
     * @param SphinxSearchQuery $query
     */
    public function applyDtypes(SphinxClient $sphinxClient, SphinxSearchQuery $query) {
        $types = $query->getQueryParameter('types', null);
        if (!$types) {
            return;
        }

        $filters = [];
        /** @var SearchRecordTypeInterface $recordType */
        foreach ($this->sphinxDTypeRecordProvider->getAll() as $recordType) {
            $type = $recordType->getApiTypeKey();
            if (in_array($type, $types)) {
                $filters[] = $recordType->getDType();
            }
        }
        $sphinxClient->setFilter('dtype', $filters);
    }

    /**
     * Return some records from sphinx results.
     *
     * @param array $search Sphinx search result.
     * @return array
     */
    protected function extractRecordsFromSphinxResult(array $search): array {
        $records = [];
        $matches = $search['matches'] ?? [];

        foreach ($matches as $guid => $record) {
            $sphinxType = $this->sphinxDTypeRecordProvider->getByDType($record['attrs']['dtype']);
            if (!$sphinxType) {
                continue;
            }

            $records[] = [
                'recordID' => $sphinxType->getRecordID($guid),
                'type' => $sphinxType->getApiTypeKey(),
            ];
        }

        return $records;
    }

    /**
     * Get a prepared, clean sphinx client.
     *
     * @return SphinxClient
     */
    protected function getSphinxClient(): SphinxClient {
        $sphinxClient = new SphinxClient();
        $sphinxClient->setServer($this->sphinxServer, $this->sphinxPort);

        $sphinxClient->setSortMode(SphinxClient::SORT_RELEVANCE);
        $sphinxClient->setRankingMode(SphinxClient::RANK_SPH04);
        $sphinxClient->setMaxQueryTime(5000);
        $sphinxClient->setFieldWeights(['name' => 3, 'body' => 1]);

        return $sphinxClient;
    }
}
