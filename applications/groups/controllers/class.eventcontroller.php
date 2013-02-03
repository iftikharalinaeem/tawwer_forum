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

         $MemberOfGroup = $GroupModel->IsMember(Gdn::Session()->UserID, $GroupID);
         if (!$MemberOfGroup)
            throw PermissionException();
         
         $this->AddBreadcrumb($Group['Name'], Url("/group/{$GroupID}"));
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
         
         print_r($Event);
         die();
      }
      
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
      
      // Lookup event
      $EventModel = new EventModel();
      $Event = $EventModel->GetID($EventID, DATASET_TYPE_ARRAY);
      if (!$Event) throw NotFoundException('Event');
      
      // Lookup group, if there is one
      $GroupID = GetValue('GroupID', $Event, FALSE);
      if ($GroupID) {
         $GroupModel = new GroupModel();
         $Group = $GroupModel->GetID($GroupID, DATASET_TYPE_ARRAY);
         if (!$Group) throw NotFoundException('Group');
         
         // Check if this person is a member of the group or a moderator
         $MemberOfGroup = $GroupModel->IsMember(Gdn::Session()->UserID, $GroupID);
         if (!$MemberOfGroup && !Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
            throw PermissionException('Group.Member');
         
         $this->AddBreadcrumb($Group['Name'], Url("/group/{$GroupID}"));
      } else {
         
         // No group, so user has to have been invited to view
         $InvitedToEvent = $EventModel->IsInvited(Gdn::Session()->UserID, $EventID);
         if (!$InvitedToEvent)
            throw PermissionException('Event.Invited');
         
      }
      
      $this->Title($Event['Name']);
      $this->AddBreadcrumb($this->Title());
      
      
      
      return $this->Render();
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