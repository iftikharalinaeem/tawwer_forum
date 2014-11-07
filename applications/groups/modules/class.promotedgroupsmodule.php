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

   public $Limit = 3;
   public $PromoteType = 'popular';
   public $myGroups = false;

   protected $PromoteTypes = array(
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

   private $Groups;
   private $title;
   private $url;
   private $orderBy;

   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'groups';
   }

   /**
    * Retrieve the groups for this module.
    *
    * @return void
    */
   public function GetData() {

       if (!array_key_exists($this->PromoteType, $this->PromoteTypes)) {
           $this->SetData('ErrorMessage', T('No such groups listing.'));
       }

       else {

           $this->title = $this->PromoteTypes[$this->PromoteType]['title'];
           $this->url = $this->PromoteTypes[$this->PromoteType]['url'];
           $this->orderBy = $this->PromoteTypes[$this->PromoteType]['orderBy'];

           //get groups
           $GroupModel = new GroupModel();

           if ($this->PromoteType === 'mine') {
              $this->Groups = $GroupModel->GetByUser(Gdn::Session()->UserID, $this->orderBy, false, 'desc', $this->Limit);
           }
           else {
              $this->Groups = $GroupModel->Get($this->orderBy, 'desc', $this->Limit)->ResultArray();
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
      $this->SetData('Groups', $this->Groups);
      $this->SetData('Title', $this->title);
      $this->SetData('Url', $this->url);

      require_once Gdn::Controller()->FetchViewLocation('group_functions', 'Group', 'groups');

      return $this->FetchView();
   }

}