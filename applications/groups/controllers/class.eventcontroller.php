<?php

/**
 * Groups Application - Event Controller
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package groups
 * @since 1.0
 */

class EventController extends Gdn_Controller {

   protected $Uses = array('Form');

   /**
    * Form
    * @var Gdn_Form
    */
   protected $Form;

   /**
    * Include JS, CSS, and modules used by all methods.
    *
    * Always called by dispatcher before controller's requested method.
    *
    * @access public
    */
   public function Initialize() {
      // Set up head
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery-ui.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddJsFile('event.js');

      // Localization of JqueryUI date picker.
      $currentLocale = Gdn::Locale()->Current();
      $parts = preg_split('`(_|-)`', $currentLocale, 2);
      if (count($parts) == 2) {
         $currentLanguage = $parts[0];
      } else {
         $currentLanguage = $currentLocale;
      }
      $currentLanguage = strtolower($currentLanguage);

      // @todo move datepicker- files into locales.
      // Other plugins could also be implementing datapicker and we don't multiple copies.
      $this->AddJsFile('datepicker-' . $currentLanguage . '.js');

      $this->addCssFile('vanillicon.css', 'static');
      $this->AddCssFile('style.css');
      Gdn_Theme::Section('Event');
      parent::Initialize();
   }

   public function Index($EventID) {
      return $this->Event($EventID);
   }

   /**
    * Common add/edit functionality
    *
    * @param integer $EventID
    */
   protected function AddEdit($EventID = NULL, $GroupID = NULL) {
      $this->Permission('Garden.SignIn.Allow');

      $this->AddJsFile('jquery.timepicker.min.js');
      $this->AddJsFile('jquery.dropdown.js');
      $this->AddCssFile('jquery.dropdown.css', 'Dashboard');

      $Event = NULL;
      $Group = NULL;

      // Lookup event
      if ($EventID) {
         $EventModel = new EventModel();
         $Event = $EventModel->GetID($EventID, DATASET_TYPE_ARRAY);
         if (!$Event) throw NotFoundException('Event');
         $this->SetData('Event', $Event);
         $GroupID = $Event['GroupID'];
      }

      // Lookup group, if there is one
      if ($GroupID) {
         $GroupModel = new GroupModel();
         $Group = $GroupModel->GetID($GroupID);
         if (!$Group) throw NotFoundException('Group');
         $this->SetData('Group', $Group);
      }

      // Add breadcrumbs
      if ($Group)
         $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));

      if ($Event)
         $this->AddBreadcrumb($Event['Name'], EventUrl($Event));

      return array($Event, $Group);
   }

   /**
    * Create a new event
    *
    * @param integer $GroupID Optional, if we're creating a group event
    * @return type
    * @throws Exception
    */
   public function Add($GroupID = NULL) {
      list($Event, $Group) = $this->AddEdit(NULL, $GroupID);

      if (!EventPermission('Create'))
         throw ForbiddenException('create a new event');

      $this->Title(T('New Event'));
      $this->AddBreadcrumb($this->Title());

      // TODO: Event create permission

      $EventModel = new EventModel();
      $this->Form->SetModel($EventModel);
      if ($this->Form->IsPostBack()) {
         $EventData = $this->Form->FormValues();

         if ($GroupID)
            $EventData['GroupID'] = $GroupID;

         // Apply munged event data back to form
         $this->Form->ClearInputs();
         $this->Form->SetFormValue($EventData);

         if ($EventID = $this->Form->Save()) {
            $EventData['EventID'] = $EventID;
            if (GetValue('GroupID',$EventData, FALSE))
               $EventModel->InviteGroup($EventID, $GroupID);

            $this->InformMessage(FormatString(T("New event created for <b>'{Name}'</b>"), $EventData));
            Redirect(EventUrl($EventData));
         }

      }

      // Pull in group and event functions
      require_once $this->FetchViewLocation('event_functions', 'Event');
      require_once $this->FetchViewLocation('group_functions', 'Group');

      $this->View = 'addedit';
      $this->CssClass .= ' NoPanel NarrowForm';
      return $this->Render();
   }

   /**
    * Edit an event
    *
    * @param type $EventID
    */
   public function Edit($EventID) {
      list($Event, $Group) = $this->AddEdit($EventID);

      if (!EventPermission('Edit'))
         throw ForbiddenException('edit this event');

      $this->Title(T('Edit Event'));
      $this->AddBreadcrumb($this->Title());
      $this->Form->SetData($Event);

      $EventModel = new EventModel();
      $this->Form->SetModel($EventModel);
      if ($this->Form->IsPostBack()) {
         $EventData = $this->Form->FormValues();

         // Re-assign IDs
         $EventData['EventID'] = $Event['EventID'];
         $EventData['GroupID'] = $Event['GroupID'];

         // Apply munged event data back to form
         $this->Form->ClearInputs();
         $this->Form->SetFormValue($EventData);

         if ($EventID = $this->Form->Save()) {
            $EventData['EventID'] = $EventID;
            if (GetValue('GroupID',$EventData, FALSE))
               $EventModel->InviteGroup($EventID, $GroupID);

            $this->InformMessage(FormatString(T("<b>'{Name}'</b> has been updated"), $EventData));
            Redirect(EventUrl($Event));
         }
      }

      // Pull in group functions
      require_once $this->FetchViewLocation('event_functions', 'Event');
      require_once $this->FetchViewLocation('group_functions', 'Group');

      $this->View = 'addedit';
      $this->CssClass .= ' NoPanel NarrowForm';
      return $this->Render();
   }

   /**
    * View an event
    *
    * @param integer $EventID Event ID (and optional -slug)
    * @return type
    * @throws Exception
    */
   public function Event($EventID) {

      // Lookup event
      $EventModel = new EventModel();
      $Event = $EventModel->GetID($EventID, DATASET_TYPE_ARRAY);
      if (!$Event) throw NotFoundException('Event');
      $ViewEvent = FALSE;

      // Check our invite status
      $InvitedToEvent = $EventModel->IsInvited(Gdn::Session()->UserID, $EventID);

      // Lookup group, if there is one
      $GroupID = GetValue('GroupID', $Event, FALSE);
      if ($GroupID) {

         $GroupModel = new GroupModel();
         $Group = $GroupModel->GetID($GroupID, DATASET_TYPE_ARRAY);
         if (!$Group) throw NotFoundException('Group');
         $this->SetData('Group', $Group);

         // Check if this person is a member of the group or a moderator
         $MemberOfGroup = $GroupModel->IsMember(Gdn::Session()->UserID, $GroupID);
         if ($MemberOfGroup || Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
            $ViewEvent = TRUE;

         $this->AddBreadcrumb('Groups', Url('/groups'));
         $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));

      } else {

         // Group organizer
         if ($Event['InsertUserID'] == Gdn::Session()->UserID)
            $ViewEvent = TRUE;

         // No group, so user has to have been invited to view
         if ($InvitedToEvent || Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
            $ViewEvent = TRUE;

         $this->AddBreadcrumb('Events', Url('/events'));
      }

      // No permission
      if (!$ViewEvent)
         throw ForbiddenException('view this event');

      $this->Title($Event['Name']);
      $this->AddBreadcrumb($this->Title());
      $this->CssClass .= ' NoPanel';

      $OrganizerID = $Event['InsertUserID'];
      $Organizer = Gdn::UserModel()->GetID($OrganizerID, DATASET_TYPE_ARRAY);
      $Event['Organizer'] = $Organizer;

      $this->SetData('Event', $Event);
      $this->SetData('Attending', $InvitedToEvent);

      if ($InvitedToEvent != 'Invited')
         $this->Form->SetValue('Attending', $InvitedToEvent);

      // Invited
      $Invited = $EventModel->Invited($EventID);
      $this->SetData('Invited', $Invited);

      // Pull in group functions
      require_once $this->FetchViewLocation('event_functions', 'Event');
      require_once $this->FetchViewLocation('group_functions', 'Group');

      $this->AddModule('DiscussionFilterModule');
      $this->RequestMethod = 'event';
      $this->View = 'event';
      return $this->Render();
   }

   /**
    * Delete an event
    *
    * @param integer $EventID
    */
   public function Delete($EventID) {
      list($Event, $Group) = $this->AddEdit($EventID);

      if (!EventPermission('Edit')) {
         throw ForbiddenException('delete this event');
      }

      if ($this->Form->AuthenticatedPostBack()) {
         $EventModel = new EventModel();
         $Deleted = $EventModel->Delete(array('EventID' => $EventID));

         if ($Deleted) {
            $this->InformMessage(FormatString(T('<b>{Name}</b> deleted.'), $Event));
            if ($Group)
               $this->RedirectUrl = GroupUrl($Group);
            else
               $this->RedirectUrl = Url('/groups');
         } else {
            $this->InformMessage(T('Failed to delete event.'));
         }
      }

      $this->SetData('Title', T('Delete Event'));
      return $this->Render();
   }

   /**
    * AJAX callback stating this user's state on the event
    *
    * @param integer $EventID
    * @param enum $Attending [Yes, No, Maybe]
    */
   public function Attending($EventID, $Attending) {
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      $this->Permission('Garden.SignIn.Allow');

      // Lookup event
      $EventModel = new EventModel();
      $Event = $EventModel->GetID($EventID, DATASET_TYPE_ARRAY);
      if (!$Event) throw NotFoundException('Event');
      $AttendEvent = FALSE;

      // Check our invite status
      $InvitedToEvent = $EventModel->IsInvited(Gdn::Session()->UserID, $EventID);

      // Lookup group, if there is one
      $GroupID = GetValue('GroupID', $Event, FALSE);
      if ($GroupID) {

         $GroupModel = new GroupModel();
         $Group = $GroupModel->GetID($GroupID, DATASET_TYPE_ARRAY);
         if (!$Group) throw NotFoundException('Group');

         // Check if this person is a member of the group or a moderator
         $MemberOfGroup = $GroupModel->IsMember(Gdn::Session()->UserID, $GroupID);
         if ($MemberOfGroup)
            $AttendEvent = TRUE;

         $this->AddBreadcrumb('Groups', Url('/groups'));
         $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));

      } else {

         // Group organizer
         if ($Event['InsertUserID'] == Gdn::Session()->UserID)
            $AttendEvent = TRUE;

         // No group, so user has to have been invited to view
         if ($InvitedToEvent)
            $AttendEvent = TRUE;

         $this->AddBreadcrumb('Events', Url('/events'));
      }

      // No permission
      if (!$AttendEvent)
         throw ForbiddenException('attend this event');
      $eventName = val('Name', $Event, t('this event'));
      $EventModel->Attend(Gdn::Session()->UserID, $EventID, $Attending);
      $this->InformMessage(sprintf(t('Your status for %s is now: <b>%s</b>'), $eventName, t($Attending)));
      $this->JsonTarget('#EventAttendees', $this->Attendees($EventID));

      $this->Render('blank', 'utility', 'dashboard');
   }

   /**
    * Generate Attendee list
    *
    * @param integer $EventID
    * @return string
    */
   protected function Attendees($EventID) {
      $EventModel = new EventModel();
      $Invited = $EventModel->Invited($EventID);
      $this->SetData('Invited', $Invited);

      $AttendeesView = $this->FetchView('attendees', 'event', 'groups');
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
