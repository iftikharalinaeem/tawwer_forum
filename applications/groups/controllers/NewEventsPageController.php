<?php

namespace Vanilla\Events;

use EventPage;
use EventsPage;
use Garden\Web\Data;
use Vanilla\Web\PageDispatchController;

class NewEventsPageController extends PageDispatchController {


    public function index(): Data {
        /** @var EventsPage $page */
        $page = $this->usePage(EventsPage::class);
        $page->initialize("Events");
        return $page->render();
    }

}
