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
      $this->AddJsFile('jquery-ui-1.8.17.custom.min.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      $this->AddCssFile('style.css');
      
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
   public function NewEvent($GroupID = NULL) {
      
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
      
      // TODO: Event create permission
      
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
      
      $this->AddBreadcrumb($Event['Name']);
      $this->Title($Event['Name']);
      
      
      
      return $this->Render();
   }
   
}