<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Vanilla\Knowledge;

use Vanilla\Knowledge\Models\PageMetaModel;

class PageController extends \Garden\Controller {
    protected $data = [];

    protected $scripts = [];

    protected $inlineScripts = [];

    protected $styles = [];

    protected $inlineStyles = [];

    protected $meta;

    /**
     * PageController constructor.
     */
    public function __construct() {
        $this->meta = new PageMetaModel();
    }

    /**
     * Magic factory initializer
     *
     * @param string $field Name of magic method to call
     *
     * @return mixed
     */
    public function __get(string $field) {
        if (method_exists($this, $field . 'Init')) {
            return $this->{$field . 'Init'}();
        }
    }

    /**
     * Return array of js scripts for the page
     *
     * @return array
     */
    public function getScripts() {
        return $this->scripts;
    }

    /**
     * Return array of js scripts to outputinline
     *
     * @return array
     */
    public function getInlineScripts() {
        return $this->inlineScripts;
    }

    /**
     * Get the stylesheets.
     *
     * @return array
     */
    public function getStyles() {
        return $this->styles;
    }

    /**
     * Get the styles to output inline.
     *
     * @return array
     */
    public function getInlineStyles() {
        return $this->inlineStyles;
    }

    /**
     * Get the page data.
     *
     * @param string $key Data key to get
     *
     * @return mixed Return page data key value
     */
    public function getData(string $key) {
        return $this->data[$key] ?? '';
    }

    /**
     * Initialize page meta tags default values
     *
     * @return $this
     */
    public function pageMetaInit() {
        $this->meta
            ->setTag('charset', ['charset' => 'utf-8'])
            ->setTag('IE=edge', ['http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge'])
            ->setTag('viewport', ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1'])
            ->setTag('format-detection', ['name' => 'format-detection', 'content' => 'telephone=no']);
        return $this;
    }

    /**
     * Initialize page SEO meta data.
     *
     * (temporary solution, need to be extended and/or refactored later)
     *
     * @return $this
     */
    public function setSeoMetaData() {
        $this->meta
            ->setLink('canonical', ['rel' => 'canonical', 'href' => $this->getCanonicalLink()]);
        $this->meta->setSeo('title', $this->getData('title'))
            ->setSeo('locale', 'en')
            ->setSeo('breadcrumb', $this->getData('breadcrumb-json'));
        return $this;
    }

    /**
     * Get canonical link
     *
     * @return string
     */
    public function getCanonicalLink() {
        return '/';
    }

    /**
     * Get breadcrumb. Placeholder at the moment.
     *
     * @param string $format Breadcrumb format: array, json etc. Default is json
     *
     * @return string
     */
    public function getBreadcrumb(string $format = 'json') {
        return '{
                 "@context": "http://schema.org",
                 "@type": "BreadcrumbList",
                 "itemListElement":
                 []
                }';
    }
}
