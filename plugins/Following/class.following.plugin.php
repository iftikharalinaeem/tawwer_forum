<?php if (!defined('APPLICATION')) exit();

/**
 * User following
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package Misc
 */

class FollowingPlugin extends Gdn_Plugin {

   public function ProfileController_BeforeProfileOptions_Handler($sender, $args) {
      $viewingUserID = Gdn::Session()->UserID;
      if ($viewingUserID == $sender->User->UserID) return;

      $isFollowing = $this->CheckIfFollowing($viewingUserID, $sender->User->UserID);
      $followText = ($isFollowing) ? "Unfollow" : "Follow";
      $sender->EventArguments['ProfileOptions'][] = [
         'Text' => sprintf(T($followText),$sender->User->Name),
         'Url' => UserUrl($sender->User, '', 'following'),
         'CssClass' => 'Hijack UserFollowButton'
      ];
   }

   /**
    *
    *
    * @param ProfileController $sender
    * @return type
    */
   public function ProfileController_Following_Create($sender) {
      $viewingUserID = Gdn::Session()->UserID;
      $args = $sender->RequestArgs;

      if (!sizeof($args)) return;
      $followedUserID = $sender->RequestArgs[0];
      $validUser = Gdn::UserModel()->GetID($followedUserID, DATASET_TYPE_ARRAY);
      if (!$validUser) return;

      $isFollowing = $this->CheckIfFollowing($viewingUserID, $followedUserID);
      if ($isFollowing) {
         // Unfollow
         Gdn::SQL()->Delete('Following',[
            'UserID' => $viewingUserID,
            'FollowedUserID' => $followedUserID
         ]);

         $sender->InformMessage(sprintf(T("No longer following %s"), $validUser['Name']));
         $sender->JsonTarget('.ProfileOptions .Dropdown .UserFollowButton', sprintf(T('Follow'), $validUser['Name']), 'Text');
      } else {
         // Follow
         Gdn::SQL()->Insert('Following',[
            'UserID' => $viewingUserID,
            'FollowedUserID' => $followedUserID
         ]);
         $sender->InformMessage(sprintf(T("Following %s"), $validUser['Name']));
         $sender->JsonTarget('.ProfileOptions .Dropdown .UserFollowButton', sprintf(T('Unfollow'), $validUser['Name']), 'Text');
      }

      $sender->Render('blank', 'utility', 'dashboard');
   }

   public function Base_Render_Before($sender) {
      if ($sender->ControllerName != 'profilecontroller') return;

      $sender->AddCssFile('following.css', 'plugins/Following');
      $userID = $sender->User->UserID;
      $module = new FollowingModule($sender);
      $module->SetUser($userID);
      $sender->AddModule($module);
   }

   protected function GetFollowersForUser($userID) {
      return Gdn::SQL()
         ->Select('f.UserID')
         ->From('Following f')
         ->Where('f.FollowedUserID', $userID)
         ->Get();
   }

   protected function GetFollowsForUser($userID) {
      return Gdn::SQL()
         ->Select('f.FollowedUserID')
         ->From('Following f')
         ->Where('f.UserID', $userID)
         ->Get();
   }

   protected function CheckIfFollowing($userID, $followedUserID) {
      $isFollowing = Gdn::SQL()
         ->Select('*')
         ->From('Following f')
         ->Where('f.UserID', $userID)
         ->Where('f.FollowedUserID', $followedUserID)
         ->Get();

      return ($isFollowing->NumRows());
   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      $structure = Gdn::Structure();
      $structure
         ->Table('Following')
         ->Column('UserID', 'int(11)', FALSE, 'primary')
         ->Column('FollowedUserID', 'int(11)', FALSE, 'primary')
         ->Set(FALSE, FALSE);
   }

}
