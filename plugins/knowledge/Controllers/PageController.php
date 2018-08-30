<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Vanilla\Knowledge;

class PageController extends \Garden\Controller {
    protected $scripts = [];

    protected $inlineScripts = [];

    protected $styles = [];

    protected $inlineStyles = [];

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
}
