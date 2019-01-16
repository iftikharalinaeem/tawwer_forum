<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Vanilla\Knowledge\Models\PageMetaModel;
use Vanilla\Knowledge\Models\ReduxAction;

/**
 * Knowledge Base base controller class.
 *
 * This controller expects most content to come from api
 */
abstract class PageController extends \Garden\Controller {
    /** @var \Gdn_Session */
    protected $session;

    protected $data = [];

    protected $scripts = [];

    protected $inlineScripts = [];

    protected $styles = [];

    protected $reduxActions = [];

    protected $inlineStyles = [];

    protected $meta;

    /**
     * @var string $_CanonicalUrl
     */
    protected $canonicalUrl;

    /**
     * PageController constructor.
     */
    public function __construct() {
        $this->meta = new PageMetaModel();
        $this->pageMetaInit();
    }

    /**
     * Add a redux action to the page.
     *
     * @param ReduxAction $action The action to add.
     */
    public function addReduxAction(ReduxAction $action) {
        $this->reduxActions[] = $action;
    }

    /**
     * Add inline script content.
     *
     * @param string $script Script string to be inlined.
     */
    public function addInlineScript(string $script) {
        $this->inlineScripts[] = $script;
    }
    /**
     * Add new element to js window object with some inline script content.
     *
     * @param string $variableName The name of the variable on window to set.
     * @param array $contents The data to JSON encode as the value of the variable.
     *
     * @return string The script contents.
     */
    public static function createInlineScriptContent(string $variableName, $contents) {
        return 'window["' . $variableName . '"]='.json_encode($contents).";\n";
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
     * Return array of js scripts to output inline.
     *
     * @return array
     */
    public function getInlineScripts() {
        return array_merge($this->inlineScripts, [self::createInlineScriptContent('__ACTIONS__', $this->reduxActions)]);
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
     * @param string $key Data key to get.
     *
     * @return mixed Return page data key value.
     */
    public function getData(string $key) {
        return $this->data[$key] ?? '';
    }

    /**
     * Initialize page meta tags default values.
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
     * Get canonical link.
     *
     * @return string
     */
    abstract public function getCanonicalLink() : string;

    /**
     * Redirect user to sign in page.
     *
     * @param string $uri URI user should be redirected back when log in.
     */
    public static function signInFirst(string $uri) {
        header('Location: /entry/signin?Target='.urlencode($uri), true, 302);
        exit();
    }
}
