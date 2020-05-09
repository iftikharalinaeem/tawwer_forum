<?php

namespace Vanilla\Events;

use EventsPage;
use Garden\Web\Data;
use Vanilla\Web\PageDispatchController;

class NewEventsPageController extends PageDispatchController {

    public function index(): Data {
        /** @var EventsPage $page */
        $page = $this->usePage(EventsPage::class);
        $page->initialize("Events", 'group', 3);
        return $page->render();
    }

}
