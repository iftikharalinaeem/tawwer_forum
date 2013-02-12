<?php if (!defined('APPLICATION')) exit();

/**
 * Groups Application - Events Controller
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package groups
 * @since 1.0
 */

class EventsController extends Gdn_Controller {
   
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
      
      $this->AddCssFile('style.css');
      Gdn_Theme::Section('Events');
      
      parent::Initialize();
   }
   
   public function Index($Context = NULL, $ContextID = NULL) {
      return $this->Events($Context, $ContextID);
   }
   
   /**
    * Show all events for the supplied context
    * 
    * If the context is null, show events the current user is invited to.
    * 
    * @param string $Context
    * @param integer $ContextID
    */
   public function Events($Context = NULL, $ContextID = NULL) {
      $this->Permission('Garden.SignIn.Allow');
      
      $EventModel = new EventModel();
      $EventCriteria = array();
      
      // Prepare RecentEventsModule
      $RecentEventsModule = new EventModule('recent');
      $RecentEventsModule->Button = FALSE;
      
      // Determine context
      switch ($Context) {
         
         // Events for this group
         case 'group':
            $GroupModel = new GroupModel();
            $Group = $GroupModel->GetID($ContextID, DATASET_TYPE_ARRAY);
            if (!$Group) 
               throw NotFoundException('Group');
            $this->SetData('Group', $Group);

            // Check if this person is a member of the group or a moderator
            $ViewGroupEvents = GroupPermission('View', $Group);
            if (!$ViewGroupEvents)
               throw PermissionException();

            $this->AddBreadcrumb('Groups', Url('/groups'));
            $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
            
            // Register GroupID as criteria
            $EventCriteria['GroupID'] = $Group['GroupID'];
            
            $RecentEventsModule->GroupID = $Group['GroupID'];
            
            $GroupModule = new GroupModule();
            $GroupModule->GroupID = $Group['GroupID'];
            $this->AddModule($GroupModule, 'Panel');
            
            break;
         
         // Events this user is invited to
         default:
            
            // Register logged-in user being invited as criteria
            $EventCriteria['Invited'] = Gdn::Session()->UserID;
            $RecentEventsModule->UserID = Gdn::Session()->UserID;
            
            break;
      }
      
      $this->Title(T('Events'));
      $this->AddBreadcrumb($this->Title());
      
      // Upcoming events
      $UpcomingRange = C('Groups.Events.UpcomingRange', '+30 days');
      $Events = $EventModel->GetUpcoming($UpcomingRange, $EventCriteria, FALSE);
      $this->SetData('UpcomingEvents', $Events);
      
      // Recent events
      $RecentRange = C('Groups.Events.RecentRange', '-10 days');
      $Events = $EventModel->GetUpcoming($RecentRange, $EventCriteria, TRUE);
      $this->SetData('RecentEvents', $Events);
      
      $this->FetchView('event_functions', 'event', 'groups');
      $this->FetchView('group_functions', 'group', 'groups');
      
      $this->RequestMethod = 'events';
      $this->View = 'events';
      $this->Render();
   }
   
}