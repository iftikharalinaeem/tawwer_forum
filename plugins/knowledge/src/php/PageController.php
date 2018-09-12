<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Vanilla\Knowledge;

use Vanilla\InjectableInterface;
use Vanilla\Knowledge\Models\PageMetaModel;
use Vanilla\Knowledge\Models\SiteContextModel;

/**
 * Knowledge Base base controller class.
 *
 * This controller expects most content to come from api
 */
class PageController extends \Garden\Controller implements InjectableInterface {
    const API_PAGE_KEY = 'page';

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
     * Get dependencies injected by the container.
     *
     * @param SiteContextModel $siteContext Needed to pass data to the frontend.
     */
    public function setDependencies(SiteContextModel $siteContext) {
        // Assemble our site context for the frontend.
        $gdnData = [
            'meta' => [
                'context' => $siteContext->getContext(),
            ],
        ];

        $this->inlineScripts[] = $this->createInlineScriptContent("gdn", $gdnData);
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
     * Get the page data from api response array
     *
     * @param string $key Data key to get
     *
     * @return array
     */
    public function getApiPageData(string $key) {
        return $this->data[self::API_PAGE_KEY][$key] ?? '';
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
        $this->meta
            ->setSeo('title', $this->getApiPageData('seoName'))
            ->setSeo('description', $this->getApiPageData('seoDescription'))
            ->setSeo('locale', \Gdn::locale()->current())
            ->setSeo('breadcrumb', $this->getData('breadcrumb-json'));
        return $this;
    }

    /**
     * Create some inline script content.
     *
     * @param string $variableName The name of the variable on window to set.
     * @param array $contents The data to JSON encode as the value of the variable.
     * @return string The script contents.
     */
    protected function createInlineScriptContent(string $variableName, $contents) {
        return 'window["' . $variableName . '"]='.json_encode($contents).";\n";
    }

    /**
     * Create an action
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    protected function createReduxAction(string $type, array &$data) {
        return [
            "type" => $type,
            "payload" => [
                "data" => $data,
            ],
        ];
    }

    /**
     * Get canonical link
     *
     * @return string
     */
    public function getCanonicalLink() {
        return $this->data[self::API_PAGE_KEY]['url'] ?? '/';
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
