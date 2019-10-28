<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Pages;

/**
 * Page class that only require a title. Useful for when the page doesn't need SEO and content is rendered in JS.
 */
class SimpleKbPage extends KbPage {

    /**
     * @inheritdoc
     */
    public function initialize(string $title = "") {
        $this
            ->setSeoRequired(false)
            ->disableSiteSectionValidation()
            ->setSeoTitle($title)
        ;
    }
}
