<?php

namespace Vanilla\Events;

use EventPage;
use EventsPage;
use Garden\Web\Data;
use Vanilla\Web\PageDispatchController;

class NewEventPageController extends PageDispatchController {

    /**
     * Render out the /event/{$id}
     *
     * @return Data
     */
    public function get(): Data {

    }

    public function index(): Data {
        $response = $this
            ->useSimplePage('New Event Page')
            ->blockRobots()
            ->render()
        ;

        return $response;
    }

}
