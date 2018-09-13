<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

/**
 * Class PageMetaModel.
 */
class PageMetaModel {
    protected $tags = [];
    protected $links = [];
    protected $seo = [];

    /**
     * Set page meta tag attributes
     *
     * @param string $tag Tag name
     * @param string $attributes Array of attributes to set for tag.
     *
     * @return PageMetaModel Self instance of
     */
    public function setTag(string $tag, array $attributes) : PageMetaModel {
        $this->tags[$tag] = $attributes;
        return $this;
    }
    /**
     * Set page meta link attributes
     *
     * @param string $key Meta tag key (internal index/name) for the link
     * @param string $attributes Array of attributes to set for link meta tag.
     *
     * @return PageMetaModel Self instance of
     */
    public function setLink(string $key, array $attributes) : PageMetaModel {
        $this->links[$key] = $attributes;
        return $this;
    }

    /**
     * Set page meta seo attribute
     *
     * @param string $seoKey Seo attribute name
     * @param string $seoValue Seo attribute value.
     *
     * @return PageMetaModel Self instance of
     */
    public function setSeo(string $seoKey, string $seoValue) : PageMetaModel {
        $this->seo[$seoKey] = $seoValue;
        return $this;
    }
    /**
     * Return page all meta data
     *
     * @return array Page meta data.
     */
    public function getPageMeta() : array {
        return [
            'tags' => array_values($this->tags),
            'links' => array_values($this->links),
            'seo' => $this->seo
        ];
    }
}
