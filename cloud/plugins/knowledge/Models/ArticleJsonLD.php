<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Web\Data;
use Vanilla\Models\SiteMeta;
use Vanilla\Web\AbstractJsonLDItem;

/**
 * Item to transform an article into some JSON-LD data.
 */
final class ArticleJsonLD extends AbstractJsonLDItem {

    const TYPE = "TechArticle";

    /** @var array */
    private $articleData;

    /** @var SiteMeta */
    private $siteMeta;

    /**
     * Constructor
     *
     * @param array $articleData
     * @param SiteMeta $siteMeta
     */
    public function __construct(array $articleData, SiteMeta $siteMeta) {
        $this->articleData = $articleData;
        $this->siteMeta = $siteMeta;
    }

    /**
     * @inheritdoc
     */
    public function calculateValue(): Data {
        return new Data([
            "@type" => self::TYPE,
            "mainEntityOfPage" => [
                "@type" => 'WebPage',
                '@id' => "https://google.com/article",
            ],
            'image' => [$this->articleData['seoImage'] ?? $this->siteMeta->getLogo()],
            'headline' => $this->articleData['name'],
            'datePublished' => $this->articleData['dateInserted'],
            'dateModified' => $this->articleData['dateUpdated'],
            'author' => [
                '@type' => 'Person',
                'name' => $this->articleData['insertUser']['name'],
            ],
            'publisher' => [
                "@type" => "Organization",
                "name" => $this->siteMeta->getOrgName(),
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $this->siteMeta->getShareImage(),
                ]
            ],
            'description' => $this->articleData['excerpt'],
        ]);
    }
}
