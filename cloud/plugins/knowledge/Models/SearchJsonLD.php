<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Web\Data;
use Vanilla\Web\AbstractJsonLDItem;

/**
 * JSON-LD record for the KB search.
 */
final class SearchJsonLD extends AbstractJsonLDItem {

    /** @var \Gdn_Request */
    private $request;

    /**
     * Constructor.
     * @param \Gdn_Request $request
     */
    public function __construct(\Gdn_Request $request) {
        $this->request = $request;
    }

    /**
     * @inheritdoc
     */
    public function calculateValue(): Data {
        return new Data([
            "@type"=> "WebSite",
            "url"=> $this->request->getSimpleUrl('/'),
            "potentialAction"=> [
                "@type"=> "SearchAction",
                "target"=> $this->request->getSimpleUrl('/kb/search?query={search_term}'),
                "query-input"=> "required name=search_term",
            ]
        ]);
    }
}
