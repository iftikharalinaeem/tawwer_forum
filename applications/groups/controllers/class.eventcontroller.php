<?php if (!defined('APPLICATION')) exit();

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
      $this->AddJsFile('jquery-ui-1.10.0.custom.min.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddJsFile('event.js');
      
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
      $this->AddJsFile('jstz.min.js');
      $this->AddCssFile('jquery.dropdown.css');
      
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
      
      // Timezones
      $this->SetData('Timezones', EventModel::Timezones());
      
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
         $Event = $this->Form->FormValues();
         
         if ($GroupID)
            $Event['GroupID'] = $GroupID;
         
         // Apply munged event data back to form
         $this->Form->ClearInputs();
         $this->Form->SetFormValue($Event);
         
         if ($EventID = $this->Form->Save()) {
            $Event['EventID'] = $EventID;
            if (GetValue('GroupID',$Event, FALSE))
               $EventModel->InviteGroup($EventID, $GroupID);

            $this->InformMessage(FormatString(T("New event created for <b>'{Name}'</b>"), $Event));
            Redirect(EventUrl($Event));
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
      
      // Pre-fill form
      if ($Event) {
         $UTC = new DateTimeZone('UTC');
         $Timezone = new DateTimeZone($Event['Timezone']);
         
         // Get TZ transition
         $Transition = array_shift($T = $Timezone->getTransitions(time(), time()));
         $Event['TimezoneAbbr'] = $Transition['abbr'];
         
         $EventStarts = new DateTime($Event['DateStarts'], $UTC);
         $EventStarts->setTimezone($Timezone);
         $Event['DateStarts'] = $EventStarts->format('m/d/Y');
         $Event['TimeStarts'] = $EventStarts->format('h:ia');

         $EventEnds = new DateTime($Event['DateEnds'], $UTC);
         $EventEnds->setTimezone($Timezone);
         $Event['DateEnds'] = $EventEnds->format('m/d/Y');
         $Event['TimeEnds'] = $EventEnds->format('h:ia');
      }
      
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
      
      if (!EventPermission('Organizer'))
         throw ForbiddenException('delete this event');
      
      if ($this->Form->IsPostBack()) {
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
      
      $EventModel->Attend(Gdn::Session()->UserID, $EventID, $Attending);
      $this->InformMessage(sprintf(T('Your status for this event is now: <b>%s</b>'), $Attending));
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
    * Lookup abbreviation for timezone
    * 
    * @param type $TimezoneID
    */
   public function GetTimezoneAbbr($TimezoneID) {
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      $this->DeliveryType(DELIVERY_TYPE_DATA);
      
      $this->SetData('TimezoneID', $TimezoneID);
      try {
         $Timezone = new DateTimeZone($TimezoneID);
         $NowTime = new DateTime('now', $Timezone);
         
         $Transition = array_shift($T = $Timezone->getTransitions(time(), time()));
         $this->SetData('Abbr', $Transition['abbr']);
         $Offset = $Timezone->getOffset($NowTime);
         $OffsetHours = ($Offset / 3600);
         $this->SetData('Offset', 'GMT '.(($OffsetHours >= 0) ? "+{$OffsetHours}" : $OffsetHours));
      } catch (Exception $Ex) {
         $this->SetData('Abbr', 'unknown');
      }
      
      $this->Render();
   }
   
}