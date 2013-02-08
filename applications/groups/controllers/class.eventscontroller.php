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
      $this->AddCssFile('groups.css');
      Gdn_Theme::Section('Events');
      
      parent::Initialize();
   }
   
   public function Index($Context = NULL, $ContextID = NULL) {
      return $this->All($Context, $ContextID);
   }
   
   /**
    * Show all events for the supplied context
    * 
    * If the context is null, show events the current user is invited to.
    * 
    * @param string $Context
    * @param integer $ContextID
    */
   public function All($Context = NULL, $ContextID = NULL) {
      Gdn_Theme::Section('Events');
      
      // Determine context
      switch ($Context) {
         
         // Events for this group
         case 'group':
            $GroupModel = new GroupModel();
            $Group = $GroupModel->GetID($ContextID, DATASET_TYPE_ARRAY);
            if (!$Group) throw NotFoundException('Group');
            $this->SetData('Group', $Group);

            // Check if this person is a member of the group or a moderator
            $MemberOfGroup = $GroupModel->IsMember(Gdn::Session()->UserID, $ContextID);
            if ($MemberOfGroup || Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
               $ViewEvent = TRUE;

            $this->AddBreadcrumb('Groups', Url('/groups'));
            $this->AddBreadcrumb($Group['Name'], GroupUrl($Group));
            break;
         
         // Events this user is invited to
         default:
            
            break;
      }
      
      $this->Title(T('Events'));
      $this->AddBreadcrumb($this->Title());
      
      $this->RequestMethod = 'all';
      $this->View = 'all';
      $this->Render();
   }
   
}