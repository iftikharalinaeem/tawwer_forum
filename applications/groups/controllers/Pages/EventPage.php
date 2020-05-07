<?php

use Vanilla\Web\ThemedPage;

class EventPage extends ThemedPage {
    /**
     * DI.
     */
    public function __construct() {
    }

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
      return "forum";
    }
}
