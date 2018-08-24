<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

class UITestsController extends VanillaController {

    public function initialize() {
        $this->Application = 'ui-tests';

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->Head = new HeadModule($this);
            $this->addCssFile('ui-tests.css');
        }

        // Call Gdn_Controller's initialize() as well.
        parent::initialize();
    }

    public function index() {
        $this->CssClass .= ' NoPanel';
        $this->render('index');
    }

    public function authentication() {
        $this->CssClass .= ' NoPanel';
        $this->renderReact();
    }
}
