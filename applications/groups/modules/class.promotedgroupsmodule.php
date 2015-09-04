<?php

/**
 * Groups Application - Promoted Groups Module
 *
 * Outputs a list of new or popular groups.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package groups
 */

class PromotedGroupsModule extends Gdn_Module {

   public $limit = 3;
   public $promoteType = 'popular';
   public $attachLastDiscussion = false;

   protected $promoteTypes = array(
     'popular' => array('title'   => 'Popular Groups',
                        'url'     => '/groups/browse/popular',
                        'orderBy' => 'CountMembers'),
     'new'     => array('title'   => 'New Groups',
                        'url'     => '/groups/browse/new',
                        'orderBy' => 'DateInserted'),
     'updated' => array('title'   => 'Recently Updated Groups',
                        'url'     => '/groups/browse/updated',
                        'orderBy' => 'DateLastComment'),
     'mine'    => array('title'   => 'My Groups',
                        'url'     => '/groups/browse/mine',
                        'orderBy' => 'DateLastComment')
   );

   private $groups;
   private $title;
   private $url;
   private $orderBy;

   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'groups';
      $this->setView('promotedgroups');
   }

   /**
    * Retrieve the groups for this module.
    *
    * @return void
    */
   public function GetData() {

       if (!array_key_exists($this->promoteType, $this->promoteTypes)) {
           $this->SetData('ErrorMessage', T('No such groups listing.'));
       }

       else {

           $this->title = $this->promoteTypes[$this->promoteType]['title'];
           $this->url = $this->promoteTypes[$this->promoteType]['url'];
           $this->orderBy = $this->promoteTypes[$this->promoteType]['orderBy'];

           //get groups
           $groupModel = new GroupModel();

           if ($this->promoteType === 'mine' && Gdn::Session()->UserID > 0) {
              $this->groups = $groupModel->GetByUser(Gdn::Session()->UserID, $this->orderBy, false, 'desc', $this->limit);
           }
           else {
              $this->groups = $groupModel->Get($this->orderBy, 'desc', $this->limit)->ResultArray();
           }
           if ($this->attachLastDiscussion) {
               $groupModel->JoinRecentPosts($this->groups);
           }
       }
   }

   /**
    * Render promoted groups
    *
    * @return type
    */
   public function ToString() {
       $this->GetData();
       $groupList = new GroupListModule($this->groups, $this->promoteType, $this->title, t("There aren't any groups yet."), 'groups-'.$this->promoteType);
       $groupList->setView($this->getView());
       return $groupList->toString();
   }

}
