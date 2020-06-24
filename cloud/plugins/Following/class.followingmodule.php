<?php if (!defined('APPLICATION')) exit();

class FollowingModule extends Gdn_Module {
   
   protected $Followees;
   protected $NumFollowees;
   
   protected $Followers;
   protected $NumFollowers;
   
   public function __construct($sender = '') {
      parent::__construct($sender);
   }
   
   public function setUser($userID) {
      $this->NumFollowees = Gdn::sql()
         ->select('f.UserID', 'Count', 'NumUsers')
         ->from('Following f')
         ->where('f.UserID', $userID)->get()->value('NumUsers', 0);
      $this->Followees = Gdn::sql()
         ->select('u.UserID, u.Name, u.Photo')
         ->from('Following f')
         ->join('User u', 'u.UserID = f.FollowedUserID')
         ->where('f.UserID', $userID)
         ->where('u.Photo is not null')
         ->get();
            
      $this->NumFollowers = Gdn::sql()
         ->select('f.UserID', 'Count', 'NumUsers')
         ->from('Following f')
         ->where('f.FollowedUserID', $userID)->get()->value('NumUsers', 0);
      $this->Followers = Gdn::sql()
         ->select('u.UserID, u.Name, u.Photo')
         ->from('Following f')
         ->join('User u', 'u.UserID = f.UserID')
         ->where('f.FollowedUserID', $userID)
         ->where('u.Photo is not null')
         ->get();
   }

   public function assetTarget() {
      return 'Panel';
   }

   public function toString() {
      $string = '';
      ob_start();
      ?>
      <div id="FollowingPluginUsers" class="Box">
         <h4><?php echo t("Friends"); ?></h4>
         <?php
         if ($this->NumFollowees) {
            echo "<div class=\"\">".sprintf(plural($this->NumFollowees, 'Following %d person', 'Following %d people'), $this->NumFollowees)."</div>\n";
            if ($this->Followees->numRows() > 0) {
               echo '<div class="FriendsList">';
               $followees = $this->Followees->resultArray(); shuffle($followees);
               $followees = array_slice($followees,0,($this->NumFollowees >= 30 ? 30 : $this->NumFollowees));
               foreach ($followees as $user) {
                  ?>
                  <div>
                     <a title="<?php echo $user['Name']; ?>" href="<?php echo url("profile/{$user['UserID']}/{$user['Name']}", TRUE); ?>">
                        <img src="<?php echo userPhotoUrl($user); ?>" />
                     </a>
                  </div>
                  <?php
               }
               unset($followees); unset($this->Followees);
               echo '</div>';
            }
         }
         
         if ($this->NumFollowers) {
            echo "<div class=\"\">".sprintf(plural($this->NumFollowers, 'Followed by %d person', 'Followed by %d people'), $this->NumFollowers)."</div>\n";
            if ($this->Followers->numRows() > 0) {
               echo '<div class="FriendsList">';
               $followers = $this->Followers->resultArray(); shuffle($followers);
               $followers = array_slice($followers,0,($this->NumFollowers >= 30 ? 30 : $this->NumFollowers));
               prev($followers);
               foreach ($followers as $user) {
                  ?>
                  <div>
                     <?php echo userPhoto($user); ?>
                  </div>
                  <?php
               }
               unset($followers); unset($this->Followers);
               echo '</div>';
            }
         }
         ?>
      </div>
      <?php
      $string = ob_get_contents();
      @ob_end_clean();
      return $string;
   }
}
