<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

class NewEventsController extends \Gdn_Controller {

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('global.js');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('style.css');
        Gdn_Theme::section('Events');

        parent::initialize();
    }


    public function index() {
        $this->permission('Garden.SignIn.Allow');
        $this->render('index');
    }

    public function event($id) {
        $this->permission('Garden.SignIn.Allow');
        $this->render('event');
    }

}
