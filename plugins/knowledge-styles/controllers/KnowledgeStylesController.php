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
        Gdn::controller()->MasterView = 'default.main';
        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->Head = new HeadModule($this);
            $this->addCssFile('knowledge-styles.css');
        }
    }

    public function index() {
        $this->render();
    }

    public function columnsThree() {
        $this->render("knowledgestyles/columnsThree", '', 'plugins/knowledge-styles');
    }

    public function columnsTwo() {
        $this->render("knowledgestyles/columnsTwo", '', 'plugins/knowledge-styles');
    }

    public function full() {
        $this->render("knowledgestyles/full", '', 'plugins/knowledge-styles');
    }

    public function panelLeft() {
        $this->render("knowledgestyles/panelLeft", '', 'plugins/knowledge-styles');
    }

    public function panelRight() {
        $this->render("knowledgestyles/panelRight", '', 'plugins/knowledge-styles');
    }

    public function panelAndNav() {
        $this->render("knowledgestyles/panelAndNav", '', 'plugins/knowledge-styles');
    }
}
