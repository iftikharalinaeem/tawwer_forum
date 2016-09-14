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
        $this->addJsFile('jquery-ui.js');
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
     * @param null $Context
     * @param null $ContextID
     * @throws Exception
     */
    public function index($Context = null, $ContextID = null) {
        return $this->events($Context, $ContextID);
    }

    /**
     * Show all events for the supplied context
     *
     * If the context is null, show events the current user is invited to.
     *
     * @param string $Context
     * @param integer $ContextID
     */
    public function events($Context = null, $ContextID = null) {
        $EventModel = new EventModel();
        $EventCriteria = array();

        // Determine context
        switch ($Context) {
            // Events for this group
            case 'group':
                $GroupModel = new GroupModel();
                $Group = $GroupModel->getID($ContextID, DATASET_TYPE_ARRAY);
                if (!$Group) {
                    throw NotFoundException('Group');
                }
                $this->setData('Group', $Group);
                $this->setData('NewButtonId', val('GroupID', $Group));

                // Check if this person is a member of the group or a moderator
                $ViewGroupEvents = groupPermission('View', $Group);
                if (!$ViewGroupEvents) {
                    throw PermissionException();
                }

                $this->addBreadcrumb('Groups', url('/groups'));
                $this->addBreadcrumb($Group['Name'], groupUrl($Group));

                // Register GroupID as criteria
                $EventCriteria['GroupID'] = $Group['GroupID'];
                break;

            // Events this user is invited to
            default:
                // Register logged-in user being invited as criteria
                $EventCriteria['Invited'] = Gdn::session()->UserID;
                break;
        }
        $this->title(t('Events'));
        $this->addBreadcrumb($this->title());
        $this->CssClass .= ' NoPanel';

        // Upcoming events
        $UpcomingRange = c('Groups.Events.UpcomingRange', '+365 days');
        $Events = $EventModel->getUpcoming($UpcomingRange, $EventCriteria);
        $this->setData('UpcomingEvents', $Events);

        // Recent events
        $RecentRange = c('Groups.Events.RecentRange', '-365 days');
        $Events = $EventModel->getUpcoming($RecentRange, $EventCriteria, true);
        $this->setData('RecentEvents', $Events);

        $this->fetchView('event_functions', 'event', 'groups');
        $this->fetchView('group_functions', 'group', 'groups');

        $this->RequestMethod = 'events';
        $this->View = 'events';
        $this->render();
    }

}
