<?php

/**
 * Class WebhooksPlugin
 */
class WebhooksPlugin extends \Gdn_Plugin {

    /**
     * WebhooksPlugin constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Runs when the plugin is enabled.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Runs structure.php on /utility/update and on enabling the plugin.
     */
    public function structure() {
        require dirname(__FILE__).'/structure.php';
    }
}
