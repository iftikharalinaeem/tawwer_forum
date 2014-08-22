<?php

/**
 * Groups Application - Group Module
 * 
 * Shows a group box with basic group info.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package groups
 * @since 1.0
 */

class PromotedGroupsModule extends Gdn_Module {

   public $Limit = 3;
   public $GroupsType = 'Popular Groups'; //or 'New Groups'

   protected $Groups;
   protected $GroupsUrl = '/groups/browse/popular';

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
       $sortBy = 'CountMembers';

       if ($this->GroupsType==='New Groups') {
           $this->GroupsUrl = '/groups/browse/new';
           $sortBy = 'DateInserted';
       }

       //get groups
       $GroupModel = new GroupModel();
       $this->Groups = $GroupModel->Get($sortBy, 'desc', $this->Limit)->ResultArray();
       $this->SetData('Groups', $this->Groups);
   }
   
   /**
    * Render promoted groups
    * 
    * @return type
    */
   public function ToString() {
      $this->GetData();
      $this->SetData('Groups', $this->Groups);
      $this->SetData('GroupsType', $this->GroupsType);
      $this->SetData('GroupsUrl', $this->GroupsUrl);

      require_once Gdn::Controller()->FetchViewLocation('group_functions', 'Group', 'groups');

      return $this->FetchView();
   }
   
}