<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;
use Vanilla\ApiUtils;
use Vanilla\Formatting\FormatCompatTrait;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Groups\Models\EventPermissions;
use Vanilla\Models\GenericRecord;
use Vanilla\Navigation\BreadcrumbModel;

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
    private $groupModel;

    /** @var EventModel */
    private $eventModel;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var RequestInterface */
    private $request;

    /**
     * DI.
     * @inheritdoc
     */
    public function __construct(GroupModel $groupModel, EventModel $eventModel, BreadcrumbModel $breadcrumbModel, RequestInterface $request) {
        parent::__construct();
        $this->groupModel = $groupModel;
        $this->eventModel = $eventModel;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->request = $request;
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
     * @param string $parentRecordType
     * @param int $parentRecordID
     *
     * @return array|null
     */
    protected function addEdit($eventID = null, ?string $parentRecordType = null, ?int $parentRecordID = null): ?array {
        $this->permission('Garden.SignIn.Allow');

        $this->addJsFile('jquery.timepicker.min.js');
        $this->addJsFile('jquery.dropdown.js');
        $this->addCssFile('jquery.dropdown.css', 'Dashboard');

        $event = null;
        $group = null;

        // Lookup event
        if ($eventID) {
            $this->eventModel->checkEventPermission(EventPermissions::EDIT, $eventID);

            $event = $this->eventModel->getID($eventID, DATASET_TYPE_ARRAY);
            if (!$event) {
                throw new NotFoundException('Event');
            }

            $this->applyFormatCompatibility($event, 'Body', 'Format');
            $this->setData('Event', $event);

            // These get ignored when editing an event.
            $parentRecordType = null;
            $parentRecordID = null;
            $breadcrumbs = $this->breadcrumbModel->getForRecord(new EventRecordType($eventID));
        } else {
            $parentRecordType = $parentRecordType ?? ForumCategoryRecordType::TYPE;
            $parentRecordID = $parentRecordID ?? -1;
            $this->eventModel->checkParentEventPermission(EventPermissions::CREATE, $parentRecordType, $parentRecordID);
            $this->setData([
                'parentRecordType' => $parentRecordType,
                'parentRecordID' => $parentRecordID,
            ]);
            $breadcrumbs = $this->breadcrumbModel->getForRecord(new GenericRecord($parentRecordType, $parentRecordID));
        }

        // This is an old controller using the new breadcrumb model, so we need to pop off the first crumb.
        // Old themes have some very, very specific breadcrumb handling.
        array_shift($breadcrumbs);

        foreach ($breadcrumbs as $crumb) {
            $this->addBreadcrumb($crumb->getName(), $crumb->getUrl());
        }

        if (!$eventID) {
            $this->addBreadcrumb(t('New Event'));
        }

        return $event;
    }

    /**
     * Create a new event
     *
     * @param int $parentRecordID Optional, if we're creating an event.
     */
    public function add($parentRecordID = null) {
        $parentRecordType = $this->request->getQuery()['parentRecordType'] ?? GroupRecordType::TYPE;
        $this->addEdit(null, $parentRecordType, $parentRecordID);

        $this->title(t('New Event'));

        $this->Form->setModel($this->eventModel);
        if ($this->Form->isPostBack()) {
            $eventData = $this->Form->formValues();

            if ($parentRecordID) {
                $eventData['ParentRecordID'] = $parentRecordID;
                $eventData['ParentRecordType'] = $parentRecordType;
            }

            // Apply munged event data back to form
            $this->Form->clearInputs();
            $this->Form->setFormValue($eventData);

            if ($eventID = $this->Form->save()) {
                $eventData['EventID'] = $eventID;
                $this->informMessage(formatString(t("New event created for <b>'{Name}'</b>"), $eventData));
                redirectTo($this->eventModel->eventUrl($eventData));
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
     * @param type $eventID
     */
    public function edit($eventID) {
        $event = $this->addEdit($eventID);
        $this->title(t('Edit Event'));
        $this->Form->setData($event);

        $this->Form->setModel($this->eventModel);
        if ($this->Form->isPostBack()) {
            $eventData = $this->Form->formValues();

            // Re-assign IDs
            $eventData['EventID'] = $event['EventID'];
            $eventData['GroupID'] = $event['GroupID'];
            $eventData['ParentRecordID'] = $event['ParentRecordID'];
            $eventData['ParentRecordType'] = $event['ParentRecordType'];

            // Apply munged event data back to form
            $this->Form->clearInputs();
            $this->Form->setFormValue($eventData);

            if ($eventID = $this->Form->save()) {
                $eventData['EventID'] = $eventID;

                $this->informMessage(formatString(t("<b>'{Name}'</b> has been updated"), $eventData));
                redirectTo($this->eventModel->eventUrl($event));
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
     * @param string $eventIDAndSlug Event ID (and optional -slug)
     */
    public function event(string $eventIDAndSlug) {
        // Lookup event
        $event = $this->eventModel->getID($eventIDAndSlug, DATASET_TYPE_ARRAY);
        if (!$event) {
            throw notFoundException('Event');
        }
        $eventID = $event['EventID'];
        $this->eventModel->checkEventPermission(EventPermissions::VIEW, $eventID);

        // Check our invite status
        $InvitedToEvent = $this->eventModel->isInvited(Gdn::session()->UserID, $eventID);

        // Apply breadcrumbs. We need to pop off the first crumb to conform with our old theme breadcrumbs.
        $crumbs = $this->breadcrumbModel->getForRecord(new EventRecordType($eventID));
        array_shift($crumbs);
        foreach ($crumbs as $crumb) {
            $this->addBreadcrumb($crumb->getName(), $crumb->getUrl());
        }

        // Lookup group, if there is one
        $parentRecordType = $event['ParentRecordType'];
        $parentRecordID = $event['ParentRecordID'];

        if ($parentRecordType === GroupRecordType::TYPE) {
            $group = $this->groupModel->getID($parentRecordID, DATASET_TYPE_ARRAY);
        }

        $this->EventArguments['Event'] = &$event;
        $this->EventArguments['Group'] = &$group;
        $this->fireEvent('EventLoaded');
        $this->setData('Group', $group ?? null);

        $this->title($event['Name']);
        $this->CssClass .= ' NoPanel';

        $OrganizerID = $event['InsertUserID'];
        $Organizer = Gdn::userModel()->getFragmentByID($OrganizerID);
        $Organizer = ApiUtils::convertInputKeys($Organizer);
        $event['Organizer'] = $Organizer;

        $this->setData('Event', $event);
        $this->setData('Attending', $InvitedToEvent);

        if ($InvitedToEvent != 'Invited') {
            $this->Form->setValue('Attending', $InvitedToEvent);
        }

        // Invited
        $Invited = $this->eventModel->invited($eventID);
        $this->setData('Invited', $Invited);

        // Pull in group functions
        require_once $this->fetchViewLocation('event_functions', 'Event');
        require_once $this->fetchViewLocation('group_functions', 'Group');

        $this->addModule('DiscussionFilterModule');
        $this->RequestMethod = 'event';
        $this->View = 'event';
        $this->render();
    }

    /**
     * Delete an event
     *
     * @param integer $eventID
     */
    public function delete($eventID) {
        $event = $this->addEdit($eventID);

        if ($this->Form->authenticatedPostBack()) {
            $deleted = $this->eventModel->delete(['EventID' => $eventID]);

            if ($deleted) {
                $this->informMessage(formatString(t('<b>{Name}</b> deleted.'), $event));
                $crumbs = $this->breadcrumbModel->getForRecord(new GenericRecord($event['ParentRecordType'], $event['ParentRecordID']));
                $secondToLastCrumb = $crumbs[count($crumbs) - 1] ?? null;
                $redirectUrl = $secondToLastCrumb ? $secondToLastCrumb->getUrl() : '/groups';
                $this->setRedirectTo($redirectUrl);
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
        // Can we attend?
        $this->eventModel->checkEventPermission(EventPermissions::ATTEND, $eventID);

        $attending = $this->Form->getFormValue('Attending');

        // Lookup event
        $event = $this->eventModel->getID($eventID, DATASET_TYPE_ARRAY);
        if (!$event) {
            throw notFoundException('Event');
        }

        // Check our invite status
        $eventName = $event['Name'] ?? t('this event');
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
        $fromParts = EventModel::formatEventDate($start);
        $toParts = EventModel::formatEventDate($end);

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
}
