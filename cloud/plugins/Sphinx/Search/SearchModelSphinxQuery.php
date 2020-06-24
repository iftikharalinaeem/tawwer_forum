<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Sphinx\Search;

use Vanilla\Adapters\SphinxClient;

/**
 * Sphinx query class for the search model.
 */
class SearchModelSphinxQuery {

    use SphinxQueryTrait;

    /** @var SphinxClient */
    private $sphinxClient;

    /**
     * Constructor.
     *
     * @param SphinxClient $sphinxClient
     */
    public function __construct(SphinxClient $sphinxClient) {
        $this->sphinxClient = $sphinxClient;
    }

    /**
     * @inheritdoc
     */
    protected function getSphinxClient(): SphinxClient {
        return $this->sphinxClient;
    }
}
