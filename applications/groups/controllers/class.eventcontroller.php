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
      $this->AddCssFile('groups.css');
      
      parent::Initialize();
   }
   
   public function Index($EventID) {
      return $this->Show($EventID);
   }
   
   /**
    * Create a new event
    * 
    * @param integer $GroupID Optional, if we're creating a group event
    * @return type
    * @throws Exception
    */
   public function Add($GroupID = NULL) {
      Gdn_Theme::Section('Event');
      $this->Permission('Garden.Signin.Allow');
      
      $this->AddJsFile('jquery.timepicker.min.js');
      $this->AddJsFile('jquery.dropdown.js');
      $this->AddJsFile('jstz.min.js');
      
      $this->AddCssFile('jquery.dropdown.css');
      
      // Lookup group, if there is one
      if ($GroupID) {
         // Lookup group
         $GroupModel = new GroupModel();
         $Group = $GroupModel->GetID($GroupID);
         if (!$Group) throw NotFoundException('Group');

         $this->SetData('GroupID', $GroupID);
         $MemberOfGroup = $GroupModel->IsMember(Gdn::Session()->UserID, $GroupID);
         if (!$MemberOfGroup)
            throw ForbiddenException('create a new event');
         
         $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
      }
      
      $this->Title(T('New Event'));
      $this->AddBreadcrumb($this->Title());
      
      // TODO: Event create permission
      
      // Timezones
      $this->SetData('Timezones', EventModel::Timezones());
      
      $EventModel = new EventModel();
      $this->Form->SetModel($EventModel);
      if ($this->Form->IsPostBack()) {
         $Event = $this->Form->FormValues();
         $Event['GroupID'] = $GroupID;
         
         try {
            // Timezone
            $Timezone = new DateTimeZone($Event['Timezone']);
            $UTC = new DateTimeZone('UTC');
         
            // Date starts
            if (!empty($Event['DateStarts'])) {
               $DateStartsStr = $Event['DateStarts'];
               $DateStartsFormat = '!m/d/Y';
               if (!empty($Event['TimeStarts'])) {
                  $DateStartsStr .= " {$Event['TimeStarts']}";
                  $DateStartsFormat .= ' h:ia';
               } else {
                  $Event['AllDayEvent'] = 1;
               }
               
               $EventDateStarts = DateTime::createFromFormat($DateStartsFormat, $DateStartsStr, $Timezone);
               $EventDateStarts->setTimezone($UTC);
               $Event['DateStarts'] = $EventDateStarts->format('Y-m-d H:i');
            } else { unset($Event['DateStarts']); }
            unset($Event['TimeStarts']);
            
            // Date ends
            if (!empty($Event['DateEnds'])) {
               $DateEndsStr = $Event['DateEnds'];
               $DateEndsFormat = '!m/d/Y';
               if (!empty($Event['TimeEnds'])) {
                  $DateEndsStr .= " {$Event['TimeEnds']}";
                  $DateEndsFormat .= ' h:ia';
               } else {
                  $Event['AllDayEvent'] = 1;
               }
               
               $EventDateEnds = DateTime::createFromFormat($DateEndsFormat, $DateEndsStr, $Timezone);
               $EventDateEnds->setTimezone($UTC);
               $Event['DateEnds'] = $EventDateEnds->format('Y-m-d H:i');
            } else { unset($Event['DateEnds']); }
            unset($Event['TimeEnds']);
            
            // Validate
            $this->Form->ClearInputs();
            $this->Form->SetFormValue($Event);
            
            if ($EventID = $this->Form->Save()) {
               $Event['EventID'] = $EventID;
               if ($GroupID)
                  $EventModel->InviteGroup($EventID, $GroupID);
               
               $this->InformMessage(FormatString(T("New event created for <b>'{Name}'</b>"), $Event));
               Redirect(EventUrl($Event));
            }
            
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex->getMessage());
         }
         
      }
      
      $this->View = 'add';
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
   public function Show($EventID) {
      Gdn_Theme::Section('Event');
      
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
         
         // Check if this person is a member of the group or a moderator
         $MemberOfGroup = $GroupModel->IsMember(Gdn::Session()->UserID, $GroupID);
         if ($MemberOfGroup || Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
            $ViewEvent = TRUE;
         
         $this->AddBreadcrumb('Groups', Url('/groups'));
         $this->AddBreadcrumb($Group['Name'], Url("/group/{$GroupID}"));
         
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
      
      $this->AddModule('DiscussionFilterModule');
      $this->RequestMethod = 'show';
      $this->View = 'show';
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
      $this->Permission('Garden.Signin.Allow');
      
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
         $this->AddBreadcrumb($Group['Name'], Url("/group/{$GroupID}"));
         
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
         $Transition = array_shift($T = $Timezone->getTransitions(time(), time()));
         $this->SetData('Abbr', $Transition['abbr']);
      } catch (Exception $Ex) {
         $this->SetData('Abbr', 'unknown');
      }
      
      $this->Render();
   }
   
}