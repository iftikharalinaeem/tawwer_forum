<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

class KnowledgeStylesController extends VanillaController {
    public function initialize() {
        parent::initialize();
        $this->Application = 'knowledge-styles';
        $this->CssClass .= ' NoPanel mainMasterPage';
        //Gdn::controller()->MasterView = 'default.main';
        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->Head = new HeadModule($this);
            $this->addCssFile('knowledge-styles.css');
        }
    }

    public function index() {
        $this->render();
    }
}
