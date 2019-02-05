<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

use Garden\Web\Data;

/**
 * Class for rendering the /kb/:urlCode page.
 */
class HomePage extends KbPage {

    /** @var KnowledgeBasePage */
    private $knowledgeBasePage;

    /**
     * Constructor for DI.
     *
     * @param KnowledgeBasePage $knowledgeBasePage
     */
    public function __construct(KnowledgeBasePage $knowledgeBasePage) {
        $this->knowledgeBasePage = $knowledgeBasePage;
    }

    /**
     * Initialize for the URL format /kb.
     */
    public function initialize() {
        $this->setSeoRequired(false)->setSeoTitle(\Gdn::translate('Help'));
    }

    /**
     * @inheritdoc
     */
    public function render(): Data {
        $kbCount = count($this->knowledgeBases);
        if ($kbCount === 1) {
            // If we have exactly 1 knowledge base we just render that knowldge base instead.
            $urlCode = $this->knowledgeBases[0]['urlCode'];
            $this->knowledgeBasePage->initialize($urlCode);
            return $this->knowledgeBasePage->render();
        }
        return parent::render();
    }
}
