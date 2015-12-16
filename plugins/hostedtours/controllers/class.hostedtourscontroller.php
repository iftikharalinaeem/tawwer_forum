<?php

/**
 * @copyright 2010-2015 Vanilla Forums, Inc
 * @license Proprietary
 */

/**
 * Hosted Tours Controller
 *
 * Allows Hosted Tours plugin to handle requests.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package internal
 */
class HostedToursController extends Gdn_Controller {

    /**
     * Reset tour
     *
     * This method also redirects to the site's homepage to begin the tour
     *
     * @param string $tourKey
     */
    public function reset($tourKey) {
        $tour = HostedToursPlugin::getTour($tourKey);
        if (!$tour) {
            return false;
        }

        if (!Gdn::session()->isValid()) {
            return false;
        }

        $tourName = val('name', $tour);
        $userID = Gdn::session()->UserID;
        $this->walkthrough()->resetTour($userID, $tourName);
        redirect('/');
    }

}