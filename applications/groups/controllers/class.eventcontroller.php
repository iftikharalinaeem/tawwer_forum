<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Groups Application - Event Controller
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class EventController extends Gdn_Controller {

    /** @var array  */
    protected $Uses = array('Form');

    /** @var Gdn_Form */
    protected $Form;

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
        $this->addJsFile('jquery-ui.js');
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
     * @param $EventID
     * @return type
     * @throws Exception
     */
    public function index($EventID) {
        return $this->event($EventID);
    }

    /**
     * Common add/edit functionality
     *
     * @param integer $EventID
     */
    protected function addEdit($EventID = null, $GroupID = null) {
        $this->permission('Garden.SignIn.Allow');

        $this->addJsFile('jquery.timepicker.min.js');
        $this->addJsFile('jquery.dropdown.js');
        $this->addCssFile('jquery.dropdown.css', 'Dashboard');

        $Event = null;
        $Group = null;

        // Lookup event
        if ($EventID) {
            $EventModel = new EventModel();
            $Event = $EventModel->getID($EventID, DATASET_TYPE_ARRAY);
            if (!$Event) throw NotFoundException('Event');
            $this->setData('Event', $Event);
            $GroupID = $Event['GroupID'];
        }

        // Lookup group, if there is one
        if ($GroupID) {
            $GroupModel = new GroupModel();
            $Group = $GroupModel->getID($GroupID);
            if (!$Group) throw NotFoundException('Group');
            $this->setData('Group', $Group);
        }

        // Add breadcrumbs
        if ($Group) {
            $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        }

        if ($Event) {
            $this->addBreadcrumb($Event['Name'], eventUrl($Event));
        }

        return array($Event, $Group);
    }

    /**
     * Create a new event
     *
     * @param integer $GroupID Optional, if we're creating a group event
     * @return type
     * @throws Exception
     */
    public function add($GroupID = null) {
        list($Event, $Group) = $this->addEdit(null, $GroupID);

        if (!EventPermission('Create')) {
            throw ForbiddenException('create a new event');
        }

        $this->title(t('New Event'));
        $this->addBreadcrumb($this->title());


        // TODO: Event create permission


        $EventModel = new EventModel();
        $this->Form->setModel($EventModel);
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
                    $EventModel->inviteGroup($EventID, $GroupID);
                }

                $this->informMessage(formatString(t("New event created for <b>'{Name}'</b>"), $EventData));
                redirect(eventUrl($EventData));
            }

        }

        // Pull in group and event functions
        require_once $this->fetchViewLocation('event_functions', 'Event');
        require_once $this->fetchViewLocation('group_functions', 'Group');

        $this->View = 'addedit';
        $this->CssClass .= ' NoPanel NarrowForm';
        return $this->render();
    }

    /**
     * Edit an event
     *
     * @param type $EventID
     */
    public function edit($EventID) {
        list($Event, $Group) = $this->addEdit($EventID);

        if (!EventPermission('Edit')) {
            throw ForbiddenException('edit this event');
        }
        $this->title(t('Edit Event'));
        $this->addBreadcrumb($this->title());
        $this->Form->setData($Event);

        $EventModel = new EventModel();
        $this->Form->setModel($EventModel);
        if ($this->Form->isPostBack()) {
            $EventData = $this->Form->FormValues();

            // Re-assign IDs
            $EventData['EventID'] = $Event['EventID'];
            $EventData['GroupID'] = $Event['GroupID'];

            // Apply munged event data back to form
            $this->Form->clearInputs();
            $this->Form->setFormValue($EventData);

            if ($EventID = $this->Form->save()) {
                $EventData['EventID'] = $EventID;
                if ($GroupID = val('GroupID',$EventData, false)) {
                    $EventModel->inviteGroup($EventID, $GroupID);
                }

                $this->informMessage(formatString(t("<b>'{Name}'</b> has been updated"), $EventData));
                redirect(eventUrl($Event));
            }
        }

        // Pull in group functions
        require_once $this->fetchViewLocation('event_functions', 'Event');
        require_once $this->fetchViewLocation('group_functions', 'Group');

        $this->View = 'addedit';
        $this->CssClass .= ' NoPanel NarrowForm';
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
        $EventModel = new EventModel();
        $Event = $EventModel->getID($EventID, DATASET_TYPE_ARRAY);
        if (!$Event) {
            throw NotFoundException('Event');
        }
        $ViewEvent = false;

        // Check our invite status
        $InvitedToEvent = $EventModel->isInvited(Gdn::session()->UserID, $EventID);

        // Lookup group, if there is one
        $GroupID = val('GroupID', $Event, false);
        $Group = false;
        if ($GroupID) {
            $GroupModel = new GroupModel();
            $Group = $GroupModel->getID($GroupID, DATASET_TYPE_ARRAY);
            if (!$Group) throw NotFoundException('Group');
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
            throw ForbiddenException('view this event');
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
        $Invited = $EventModel->invited($EventID);
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
     * @param integer $EventID
     */
    public function delete($EventID) {
        list($Event, $Group) = $this->addEdit($EventID);

        if (!eventPermission('Edit')) {
            throw ForbiddenException('delete this event');
        }

        if ($this->Form->authenticatedPostBack()) {
            $EventModel = new EventModel();
            $Deleted = $EventModel->delete(array('EventID' => $EventID));

            if ($Deleted) {
                $this->informMessage(formatString(t('<b>{Name}</b> deleted.'), $Event));
                if ($Group) {
                    $this->RedirectUrl = groupUrl($Group);
                } else {
                    $this->RedirectUrl = url('/groups');
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
     * @param integer $EventID
     * @param string $Attending [Yes, No, Maybe]
     */
    public function attending($EventID, $Attending) {
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->permission('Garden.SignIn.Allow');

        // Lookup event
        $EventModel = new EventModel();
        $Event = $EventModel->getID($EventID, DATASET_TYPE_ARRAY);
        if (!$Event) {
            throw NotFoundException('Event');
        }
        $AttendEvent = false;

        // Check our invite status
        $InvitedToEvent = $EventModel->isInvited(Gdn::session()->UserID, $EventID);

        // Lookup group, if there is one
        $GroupID = val('GroupID', $Event, false);
        if ($GroupID) {
            $GroupModel = new GroupModel();
            $Group = $GroupModel->getID($GroupID, DATASET_TYPE_ARRAY);
            if (!$Group) {
                throw NotFoundException('Group');
            }

            // Check if this person is a member of the group or a moderator
            $MemberOfGroup = $GroupModel->isMember(Gdn::session()->UserID, $GroupID);
            if ($MemberOfGroup) {
                $AttendEvent = true;
            }

            $this->addBreadcrumb('Groups', url('/groups'));
            $this->addBreadcrumb($Group['Name'], groupUrl($Group));
        } else {
            // Group organizer
            if ($Event['InsertUserID'] == Gdn::session()->UserID) {
                $AttendEvent = true;
            }

            // No group, so user has to have been invited to view
            if ($InvitedToEvent) {
                $AttendEvent = true;
            }

            $this->addBreadcrumb('Events', url('/events'));
        }

        // No permission
        if (!$AttendEvent) {
            throw ForbiddenException('attend this event');
        }
        $eventName = val('Name', $Event, t('this event'));
        $EventModel->attend(Gdn::session()->UserID, $EventID, $Attending);

        $this->informMessage(sprintf(t('Your status for %s is now: <b>%s</b>'), $eventName, t($Attending)));
        $this->jsonTarget('#EventAttendees', $this->attendees($EventID));
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Generate Attendee list
     *
     * @param integer $EventID
     * @return string
     */
    protected function attendees($EventID) {
        $EventModel = new EventModel();
        $Invited = $EventModel->invited($EventID);
        $this->setData('Invited', $Invited);

        $AttendeesView = $this->fetchView('attendees', 'event', 'groups');
        unset($this->Data['Invited']);

        return $AttendeesView;
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
        $fromParts = $this->formatEventDate($start);
        $toParts = $this->formatEventDate($end);

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
     * Format a date using the current timezone.
     *
     * This is sort of a stop-gap until the **Gdn_Format::*** methods.
     *
     * @param string $dateString
     * @return array
     */
    private function formatEventDate($dateString, $from = true) {
        if (!$dateString) {
            return ['', '', '', ''];
        }
        if (method_exists(Gdn::session(), 'getTimeZone')) {
            $tz = Gdn::session()->getTimeZone();
        } else {
            $tz = new DateTimeZone('UTC');
        }

        $timestamp = Gdn_Format::toTimestamp($dateString);
        if (!$timestamp) {
            return [false, false, false, false];
        }

        $dt = new DateTime('@'.$timestamp);
        $dt->setTimezone($tz);

        $offTimestamp = $timestamp + $dt->getOffset();

        $dateFormat = '%A, %B %e, %G';
        $dateStr = strftime($dateFormat, $offTimestamp);
        $timeFormat = t('Date.DefaultTimeFormat', '%l:%M%p');
        $timeStr = strftime($timeFormat, $offTimestamp);

        return [$dateStr, $timeStr, $dt->format('H:i'), $dt->format('c')];
    }
}
