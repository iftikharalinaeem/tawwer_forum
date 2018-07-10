<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Groups Application - Events Controller
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class EventsController extends Gdn_Controller {

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize() {
        // Set up head
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery-ui.min.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');
        $this->addJsFile('event.js');
        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('style.css');
        Gdn_Theme::section('Events');

        parent::initialize();
    }

    /**
     *
     *
     * @param null $context
     * @param null $contextID
     * @throws Exception
     */
    public function index($context = null, $contextID = null) {
        return $this->events($context, $contextID);
    }

    /**
     * Show all events for the supplied context
     *
     * If the context is null, show events the current user is invited to.
     *
     * @param string $context
     * @param integer $contextID
     */
    public function events($context = null, $contextID = null) {
        $eventModel = new EventModel();
        $eventCriteria = [];

        // Determine context
        switch ($context) {
            // Events for this group
            case 'group':
                $groupModel = new GroupModel();
                $group = $groupModel->getID($contextID, DATASET_TYPE_ARRAY);
                if (!$group || !$groupModel->checkPermission('Access', $group)) {
                    throw notFoundException('Group');
                }

                $this->EventArguments['Group'] = &$group;
                $this->fireEvent('GroupLoaded');

                $this->setData('Group', $group);
                $this->setData('NewButtonId', val('GroupID', $group));

                // Check if this person is a member of the group or a moderator
                $viewGroupEvents = groupPermission('View', $group);
                if (!$viewGroupEvents) {
                    throw permissionException();
                }

                $this->addBreadcrumb('Groups', url('/groups'));
                $this->addBreadcrumb($group['Name'], groupUrl($group));

                // Register GroupID as criteria
                $eventCriteria['GroupID'] = $group['GroupID'];
                break;

            // Events this user is invited to
            default:
                // Register logged-in user being invited as criteria
                $eventCriteria['Invited'] = Gdn::session()->UserID;
                break;
        }
        $this->title(t('Events'));
        $this->addBreadcrumb($this->title());
        $this->CssClass .= ' NoPanel';

        // Upcoming events
        $upcomingRange = c('Groups.Events.UpcomingRange', '+365 days');
        $upcomingEvents = $eventModel->getUpcoming($upcomingRange, $eventCriteria);

        // Recent events
        $recentRange = c('Groups.Events.RecentRange', '-365 days');
        $recentEvents = $eventModel->getUpcoming($recentRange, $eventCriteria, true);

        $this->EventArguments['UpcomingEvents'] = &$upcomingEvents;
        $this->EventArguments['RecentEvents'] = &$recentEvents;
        $this->fireEvent('EventsLoaded');

        $this->setData('UpcomingEvents', $upcomingEvents);
        $this->setData('RecentEvents', $recentEvents);

        $this->fetchView('event_functions', 'event', 'groups');
        $this->fetchView('group_functions', 'group', 'groups');

        $this->RequestMethod = 'events';
        $this->View = 'events';
        $this->render();
    }

}
