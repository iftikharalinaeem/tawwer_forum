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

   public function profileController_beforeProfileOptions_handler($sender, $args) {
      $viewingUserID = Gdn::session()->UserID;
      if ($viewingUserID == $sender->User->UserID) return;

      $isFollowing = $this->checkIfFollowing($viewingUserID, $sender->User->UserID);
      $followText = ($isFollowing) ? "Unfollow" : "Follow";
      $sender->EventArguments['ProfileOptions'][] = [
         'Text' => sprintf(t($followText),$sender->User->Name),
         'Url' => userUrl($sender->User, '', 'following'),
         'CssClass' => 'Hijack UserFollowButton'
      ];
   }

   /**
    *
    *
    * @param ProfileController $sender
    * @return type
    */
   public function profileController_following_create($sender) {
      $sender->permission('Garden.Profiles.View');

      $viewingUserID = Gdn::session()->UserID;
      $args = $sender->RequestArgs;

      if (!sizeof($args)) return;
      $followedUserID = $sender->RequestArgs[0];
      $validUser = Gdn::userModel()->getID($followedUserID, DATASET_TYPE_ARRAY);
      if (!$validUser) return;

      $isFollowing = $this->checkIfFollowing($viewingUserID, $followedUserID);
      if ($isFollowing) {
         // Unfollow
         Gdn::sql()->delete('Following',[
            'UserID' => $viewingUserID,
            'FollowedUserID' => $followedUserID
         ]);

         $sender->informMessage(sprintf(t("No longer following %s"), $validUser['Name']));
         $sender->jsonTarget('.ProfileOptions .Dropdown .UserFollowButton', sprintf(t('Follow'), $validUser['Name']), 'Text');
      } else {
         // Follow
         Gdn::sql()->insert('Following',[
            'UserID' => $viewingUserID,
            'FollowedUserID' => $followedUserID
         ]);
         $sender->informMessage(sprintf(t("Following %s"), $validUser['Name']));
         $sender->jsonTarget('.ProfileOptions .Dropdown .UserFollowButton', sprintf(t('Unfollow'), $validUser['Name']), 'Text');
      }

      $sender->render('blank', 'utility', 'dashboard');
   }

   public function base_render_before($sender) {
      if ($sender->ControllerName != 'profilecontroller') return;

      $sender->addCssFile('following.css', 'plugins/Following');
      $userID = $sender->User->UserID;
      $module = new FollowingModule($sender);
      $module->setUser($userID);
      $sender->addModule($module);
   }

   protected function getFollowersForUser($userID) {
      return Gdn::sql()
         ->select('f.UserID')
         ->from('Following f')
         ->where('f.FollowedUserID', $userID)
         ->get();
   }

   protected function getFollowsForUser($userID) {
      return Gdn::sql()
         ->select('f.FollowedUserID')
         ->from('Following f')
         ->where('f.UserID', $userID)
         ->get();
   }

   protected function checkIfFollowing($userID, $followedUserID) {
      $isFollowing = Gdn::sql()
         ->select('*')
         ->from('Following f')
         ->where('f.UserID', $userID)
         ->where('f.FollowedUserID', $followedUserID)
         ->get();

      return ($isFollowing->numRows());
   }

   public function setup() {
      $this->structure();
   }

   public function structure() {
      $structure = Gdn::structure();
      $structure
         ->table('Following')
         ->column('UserID', 'int(11)', FALSE, 'primary')
         ->column('FollowedUserID', 'int(11)', FALSE, 'primary')
         ->set(FALSE, FALSE);
   }

}
