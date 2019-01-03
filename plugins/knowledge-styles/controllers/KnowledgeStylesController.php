<?php
/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

class KnowledgeStylesController extends VanillaController {
    public function initialize() {
        parent::initialize();
        $this->Application = 'knowledge-styles';
        $this->CssClass .= ' NoPanel mainMasterPage';
        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->Head = new HeadModule($this);
            $this->addCssFile('knowledge-styles.css');
        }
    }

    public function index() {
        Gdn::controller()->MasterView = 'default.knowledge';
        $this->render();
    }

    // Pages
    public function pageHome() {
        Gdn::controller()->MasterView = 'default.knowledge';
        $this->render("knowledgestyles/components/home", '', 'plugins/knowledge-styles');
    }

    public function pageArticle() {
        Gdn::controller()->MasterView = 'default.knowledge';
        $this->render("knowledgestyles/components/article", '', 'plugins/knowledge-styles');
    }

    public function pageCategory() {
        Gdn::controller()->MasterView = 'default.knowledge';
        $this->render("knowledgestyles/components/category", '', 'plugins/knowledge-styles');
    }

    public function pageCategoryWithNav() {
        Gdn::controller()->MasterView = 'default.knowledge';
        $this->render("knowledgestyles/components/categoryWithNav", '', 'plugins/knowledge-styles');
    }

    public function pageAdvancedSearch() {
        Gdn::controller()->MasterView = 'default.knowledge';
        $this->render("knowledgestyles/components/advancedSearch", '', 'plugins/knowledge-styles');
    }
}
