<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Vanilla\Formatting\FormatCompatTrait;

/**
 * Groups Application - Event Controller
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class EventController extends Gdn_Controller {

    use FormatCompatTrait;

    /** @var array  */
    protected $Uses = ['Form', 'GroupModel'];

    /** @var Gdn_Form */
    protected $Form;

    /** @var GroupModel */
    protected $GroupModel;

    /** @var \EventModel */
    private $eventModel;

    /**
     * EventController constructor.
     * @param EventModel $eventModel
     */
    public function __construct(\EventModel $eventModel) {
        $this->eventModel = $eventModel;
        parent::__construct();
    }

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @access public
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

        // Localization of JqueryUI date picker.
        $currentLocale = Gdn::locale()->current();
        $parts = preg_split('`(_|-)`', $currentLocale, 2);
        if (count($parts) == 2) {
            $currentLanguage = $parts[0];
        } else {
            $currentLanguage = $currentLocale;
        }
        $currentLanguage = strtolower($currentLanguage);

        // @todo move datepicker- files into locales.
        // Other plugins could also be implementing datapicker and we don't multiple copies.
        $this->addJsFile('datepicker-' . $currentLanguage . '.js');

        $this->addCssFile('vanillicon.css', 'static');
        $this->addCssFile('style.css');
        Gdn_Theme::section('Event');
        parent::initialize();
    }

    /**
     *
     *
     * @param $eventID
     * @return type
     * @throws Exception
     */
    public function index($eventID) {
        return $this->event($eventID);
    }

    /**
     * Common add/edit functionality
     *
     * @param integer $eventID
     */
    protected function addEdit($eventID = null, $groupID = null) {
        $this->permission('Garden.SignIn.Allow');

        $this->addJsFile('jquery.timepicker.min.js');
        $this->addJsFile('jquery.dropdown.js');
        $this->addCssFile('jquery.dropdown.css', 'Dashboard');

        $event = null;
        $group = null;

        // Lookup event
        if ($eventID) {
            $event = $this->eventModel->getID($eventID, DATASET_TYPE_ARRAY);
            if (!$event) throw notFoundException('Event');

            $this->applyFormatCompatibility($event, 'Body', 'Format');
            $this->setData('Event', $event);
            $groupID = $event['GroupID'];
        }

        // Lookup group, if there is one
        if ($groupID) {
            $groupModel = new GroupModel();
            $group = $groupModel->getID($groupID);
            $this->verifyGroupAccess($group);
            $this->setData('Group', $group);
        }

        // Add breadcrumbs
        if ($group) {
            $this->addBreadcrumb($group['Name'], groupUrl($group));
        }

        if ($event) {
            $this->addBreadcrumb($event['Name'], eventUrl($event));
        }

        return [$event, $group];
    }

    /**
     * Create a new event
     *
     * @param integer $GroupID Optional, if we're creating a group event
     * @throws Exception
     */
    public function add($GroupID = null) {
        list($Event, $Group) = $this->addEdit(null, $GroupID);

        if ($GroupID) {
            if(!groupPermission('Member')) {
                throw forbiddenException('@' . groupPermission('View.Reason', $GroupID));
            }
        }

        if (!eventPermission('Create')) {
            throw forbiddenException('create a new event');
        }

        $this->title(t('New Event'));
        $this->addBreadcrumb($this->title());


        // TODO: Event create permission

        $this->Form->setModel($this->eventModel);
        if ($this->Form->isPostBack()) {
            $EventData = $this->Form->formValues();

            if ($GroupID) {
                $EventData['GroupID'] = $GroupID;
            }

            // Apply munged event data back to form
            $this->Form->clearInputs();
            $this->Form->setFormValue($EventData);

            if ($EventID = $this->Form->save()) {
                $EventData['EventID'] = $EventID;
                if (val('GroupID',$EventData, false)) {
                    $this->eventModel->inviteGroup($EventID, $GroupID);
                }

                $this->informMessage(formatString(t("New event created for <b>'{Name}'</b>"), $EventData));
                redirectTo(eventUrl($EventData));
            }

        }

        // Pull in group and event functions
        require_once $this->fetchViewLocation('event_functions', 'Event');
        require_once $this->fetchViewLocation('group_functions', 'Group');

        $this->View = 'addedit';
        $this->CssClass .= ' NoPanel';
        return $this->render();
    }

    /**
     * Edit an event
     *
     * @param type $EventID
     */
    public function edit($EventID) {
        list($Event, $Group) = $this->addEdit($EventID);

        if (!eventPermission('Edit')) {
            throw forbiddenException('edit this event');
        }
        $this->title(t('Edit Event'));
        $this->addBreadcrumb($this->title());
        $this->Form->setData($Event);

        $this->Form->setModel($this->eventModel);
        if ($this->Form->isPostBack()) {
            $EventData = $this->Form->formValues();

            // Re-assign IDs
            $EventData['EventID'] = $Event['EventID'];
            $EventData['GroupID'] = $Event['GroupID'];

            // Apply munged event data back to form
            $this->Form->clearInputs();
            $this->Form->setFormValue($EventData);

            if ($EventID = $this->Form->save()) {
                $EventData['EventID'] = $EventID;
                if ($GroupID = val('GroupID',$EventData, false)) {
                    $this->eventModel->inviteGroup($EventID, $GroupID);
                }

                $this->informMessage(formatString(t("<b>'{Name}'</b> has been updated"), $EventData));
                redirectTo(eventUrl($Event));
            }
        }

        // Pull in group functions
        require_once $this->fetchViewLocation('event_functions', 'Event');
        require_once $this->fetchViewLocation('group_functions', 'Group');

        $this->View = 'addedit';
        $this->CssClass .= ' NoPanel';
        return $this->render();
    }

    /**
     * View an event.
     *
     * @param integer $EventID Event ID (and optional -slug)
     * @return type
     * @throws Exception
     */
    public function event($EventID) {
        // Lookup event
        $Event = $this->eventModel->getID($EventID, DATASET_TYPE_ARRAY);
        if (!$Event) {
            throw notFoundException('Event');
        }
        $ViewEvent = false;

        // Check our invite status
        $InvitedToEvent = $this->eventModel->isInvited(Gdn::session()->UserID, $EventID);

        // Lookup group, if there is one
        $GroupID = val('GroupID', $Event, false);
        $Group = false;
        if ($GroupID) {
            $GroupModel = new GroupModel();
            $Group = $GroupModel->getID($GroupID, DATASET_TYPE_ARRAY);
            $this->verifyGroupAccess($Group);
        }

        $this->EventArguments['Event'] = &$Event;
        $this->EventArguments['Group'] = &$Group;
        $this->fireEvent('EventLoaded');

        if ($Group) {
            $this->setData('Group', $Group);

            // Check if this person is a member of the group or a moderator
            $MemberOfGroup = $GroupModel->isMember(Gdn::session()->UserID, $GroupID);
            if ($MemberOfGroup || Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
                $ViewEvent = true;
            }

            $this->addBreadcrumb('Groups', url('/groups'));
            $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        } else {
            // Group organizer
            if ($Event['InsertUserID'] == Gdn::session()->UserID) {
                $ViewEvent = true;
            }

            // No group, so user has to have been invited to view
            if ($InvitedToEvent || checkPermission('Garden.Moderation.Manage')) {
                $ViewEvent = true;
            }

            $this->addBreadcrumb('Events', url('/events'));
        }

        // No permission
        if (!$ViewEvent) {
            throw forbiddenException('view this event');
        }

        $this->title($Event['Name']);
        $this->addBreadcrumb($this->title());
        $this->CssClass .= ' NoPanel';

        $OrganizerID = $Event['InsertUserID'];
        $Organizer = Gdn::userModel()->getID($OrganizerID, DATASET_TYPE_ARRAY);
        $Event['Organizer'] = $Organizer;

        $this->setData('Event', $Event);
        $this->setData('Attending', $InvitedToEvent);

        if ($InvitedToEvent != 'Invited') {
            $this->Form->setValue('Attending', $InvitedToEvent);
        }

        // Invited
        $Invited = $this->eventModel->invited($EventID);
        $this->setData('Invited', $Invited);

        // Pull in group functions
        require_once $this->fetchViewLocation('event_functions', 'Event');
        require_once $this->fetchViewLocation('group_functions', 'Group');

        $this->addModule('DiscussionFilterModule');
        $this->RequestMethod = 'event';
        $this->View = 'event';
        return $this->render();
    }

    /**
     * Delete an event
     *
     * @param integer $eventID
     */
    public function delete($eventID) {
        list($event, $group) = $this->addEdit($eventID);

        if (!eventPermission('Edit')) {
            throw forbiddenException('delete this event');
        }

        if ($this->Form->authenticatedPostBack()) {
            $deleted = $this->eventModel->delete(['EventID' => $eventID]);

            if ($deleted) {
                $this->informMessage(formatString(t('<b>{Name}</b> deleted.'), $event));
                if ($group) {
                    $this->setRedirectTo(groupUrl($group));
                } else {
                    $this->setRedirectTo('/groups');
                }
            } else {
                $this->informMessage(t('Failed to delete event.'));
            }
        }

        $this->setData('Title', t('Delete Event'));
        return $this->render();
    }

    /**
     * AJAX callback stating this user's state on the event.
     *
     * @param integer $eventID
     * @param string $attending [Yes, No, Maybe]
     */
    public function attending() {
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->permission('Garden.SignIn.Allow');

        if (!$this->Form->authenticatedPostBack()) {
            throw forbiddenException('GET');
        }

        $eventID = $this->Form->getFormValue('EventID');
        $attending = $this->Form->getFormValue('Attending');

        // Lookup event
        $event = $this->eventModel->getID($eventID, DATASET_TYPE_ARRAY);
        if (!$event) {
            throw notFoundException('Event');
        }
        $attendEvent = false;

        // Check our invite status
        $invitedToEvent = $this->eventModel->isInvited(Gdn::session()->UserID, $eventID);

        // Lookup group, if there is one
        $groupID = val('GroupID', $event, false);
        if ($groupID) {
            $groupModel = new GroupModel();
            $group = $groupModel->getID($groupID, DATASET_TYPE_ARRAY);
            $this->verifyGroupAccess($group);

            // Check if this person is a member of the group or a moderator
            $memberOfGroup = $groupModel->isMember(Gdn::session()->UserID, $groupID);
            if ($memberOfGroup) {
                $attendEvent = true;
            }

            $this->addBreadcrumb('Groups', url('/groups'));
            $this->addBreadcrumb($group['Name'], groupUrl($group));
        } else {
            // Group organizer
            if ($event['InsertUserID'] == Gdn::session()->UserID) {
                $attendEvent = true;
            }

            // No group, so user has to have been invited to view
            if ($invitedToEvent) {
                $attendEvent = true;
            }

            $this->addBreadcrumb('Events', url('/events'));
        }

        // No permission
        if (!$attendEvent) {
            throw forbiddenException('attend this event');
        }
        $eventName = val('Name', $event, t('this event'));
        $this->eventModel->attend(Gdn::session()->UserID, $eventID, $attending);

        $this->informMessage(sprintf(t('Your status for %s is now: <b>%s</b>'), htmlspecialchars($eventName), t($attending)));
        $this->jsonTarget('#EventAttendees', $this->attendees($eventID));
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Generate Attendee list
     *
     * @param integer $eventID
     * @return string
     */
    protected function attendees($eventID) {
        $invited = $this->eventModel->invited($eventID);
        $this->setData('Invited', $invited);

        $attendeesView = $this->fetchView('attendees', 'event', 'groups');
        unset($this->Data['Invited']);

        return $attendeesView;
    }

    /**
     * Return the HTML for the controls required by a js-datetime-picker.
     *
     * @param string $sx The suffix of the database field (ex. Starts, Ends).
     * @return string Returns the control's HTML.
     */
    public function dateTimePicker($sx, $emptyTime = '') {
        $form = $this->Form;
        $result = '<span class="js-datetime-picker">';

        $result .= $form->textBox("RawDate$sx", [
             'class' => 'InputBox DatePicker',
             'title' => t("Date. Expects 'mm/dd/yyyy'.")
        ]);

        $result .= ' '.$form->textBox("Time$sx", [
             'class' => 'InputBox TimePicker',
             'placeholder' => t('Add a time?'),
             'data-empty' => $emptyTime
        ]);

        if (!$form->isPostBack()) {
            // Format the date as ISO 8601 so that javascript can recognize it better.
            $date = $form->getValue("Date$sx");
            if ($date) {
                $timestamp = Gdn_Format::toTimestamp($date);
                $form->setValue("Date$sx", gmdate('c', $timestamp));
            }
        }

        $result .= $form->hidden("Date$sx").'</span>';

        return $result;
    }

    /**
     * Format the start/end times of an event.
     *
     * This method intelligently determines whether or not to add the times to the dates and where to show both the start
     * and end date or just the start date if its a one day event.
     *
     * @param string $start The UTC start time of the event.
     * @param string $end The UTC end time of the event.
     * @return string Returns the formatted dates.
     */
    public function formatEventDates($start, $end) {
        $fromParts = $this->eventModel::formatEventDate($start);
        $toParts = $this->eventModel::formatEventDate($end);

        $fromStr = $fromParts[0];
        $toStr = $toParts[0];

        // Add the times only if we aren't on a date boundary.
        if ($fromParts[1] && !($fromParts[2] === '00:00' && ($toParts[2] === '23:59' || $toParts[2] === ''))) {
            $fmt = t('{Date} at {Time}');
            $fromStr = formatString($fmt, ['Date' => $fromStr, 'Time' => $fromParts[1]]);

            if ($toParts[2]) {
                $toStr = formatString($fmt, ['Date' => $toStr, 'Time' => $toParts[1]]);
            }
        }

        if ($fromStr === $toStr || !$toStr) {
            return wrap($fromStr, 'time', ['datetime' => $fromParts[3]]);
        } else {
            return sprintf(
                 t('%s <b>until</b> %s'),
                 wrap($fromStr, 'time', ['datetime' => $fromParts[3]]),
                 wrap($toStr, 'time', ['datetime' => $toParts[3]])
            );
        }
    }



    /**
     * Verify a user can access a group.
     *
     * @param array $group A group row.
     * @throws Gdn_UserException If the group cannot be accessed by the current user.
     */
    private function verifyGroupAccess($group) {
        if (!$group || !$this->GroupModel->checkPermission('Access', $group)) {
            throw notFoundException('Group');
        }
    }
}
