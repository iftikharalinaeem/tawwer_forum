<?php

namespace Vanilla\Theme\Controllers\Pages;


class ThemeEditorPage extends \Vanilla\Web\Page {
    /**
     * @inheritdoc
     */
    public function initialize(string $title = "") {
        $this
            ->setSeoRequired(false)
            ->setSeoTitle($title)
        ;
    }

    /**
     * Get the section of the site we are serving assets for.
     */
    public function getAssetSection(): string {
        return "admin";
    }
}
