<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Sphinx\Search;

use Vanilla\Adapters\SphinxClient;
use Vanilla\Search\SearchQuery;

/**
 * Sphinx version of a search query.
 */
class SphinxSearchQuery extends SearchQuery {

    use SphinxQueryTrait;

    /** @var SphinxClient */
    private $sphinxClient;

    /**
     * Create a query.
     *
     * @param SphinxClient $sphinxClient The sphinx client.
     * @inheritdoc
     */
    public function __construct(SphinxClient $sphinxClient, array $searchTypes, array $queryData) {
        $this->sphinxClient = $sphinxClient;
        parent::__construct($searchTypes, $queryData);
    }

    /**
     * @inheritdoc
     */
    protected function getSphinxClient(): SphinxClient {
        return $this->sphinxClient;
    }
}
