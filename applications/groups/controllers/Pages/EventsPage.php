<?php

use Vanilla\Web\Page;
use Vanilla\Web\ThemedPage;

class EventsPage extends Page {
    /**
     * DI.
     */
    public function __construct() {
    }



    public function initialize(string $title ="", string $parentRecordType="", int $parentRecordID = null) {
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
