<?php if (!defined('APPLICATION')) exit();

class FollowingModule extends Gdn_Module {
   
   protected $Followees;
   protected $NumFollowees;
   
   protected $Followers;
   protected $NumFollowers;
   
   public function __construct($sender = '') {
      parent::__construct($sender);
   }
   
   public function SetUser($userID) {
      $this->NumFollowees = Gdn::SQL()
         ->Select('f.UserID', 'Count', 'NumUsers')
         ->From('Following f')
         ->Where('f.UserID', $userID)->Get()->Value('NumUsers', 0);
      $this->Followees = Gdn::SQL()
         ->Select('u.UserID, u.Name, u.Photo')
         ->From('Following f')
         ->Join('User u', 'u.UserID = f.FollowedUserID')
         ->Where('f.UserID', $userID)
         ->Where('u.Photo is not null')
         ->Get();
            
      $this->NumFollowers = Gdn::SQL()
         ->Select('f.UserID', 'Count', 'NumUsers')
         ->From('Following f')
         ->Where('f.FollowedUserID', $userID)->Get()->Value('NumUsers', 0);
      $this->Followers = Gdn::SQL()
         ->Select('u.UserID, u.Name, u.Photo')
         ->From('Following f')
         ->Join('User u', 'u.UserID = f.UserID')
         ->Where('f.FollowedUserID', $userID)
         ->Where('u.Photo is not null')
         ->Get();
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      $string = '';
      ob_start();
      ?>
      <div id="FollowingPluginUsers" class="Box">
         <h4><?php echo T("Friends"); ?></h4>
         <?php
         if ($this->NumFollowees) {
            echo "<div class=\"\">".sprintf(Plural($this->NumFollowees, 'Following %d person', 'Following %d people'), $this->NumFollowees)."</div>\n";
            if ($this->Followees->NumRows() > 0) {
               echo '<div class="FriendsList">';
               $followees = $this->Followees->ResultArray(); shuffle($followees);
               $followees = array_slice($followees,0,($this->NumFollowees >= 30 ? 30 : $this->NumFollowees));
               foreach ($followees as $user) {
                  ?>
                  <div>
                     <a title="<?php echo $user['Name']; ?>" href="<?php echo Url("profile/{$user['UserID']}/{$user['Name']}", TRUE); ?>">
                        <img src="<?php echo UserPhotoUrl($user); ?>" />
                     </a>
                  </div>
                  <?php
               }
               unset($followees); unset($this->Followees);
               echo '</div>';
            }
         }
         
         if ($this->NumFollowers) {
            echo "<div class=\"\">".sprintf(Plural($this->NumFollowers, 'Followed by %d person', 'Followed by %d people'), $this->NumFollowers)."</div>\n";
            if ($this->Followers->NumRows() > 0) {
               echo '<div class="FriendsList">';
               $followers = $this->Followers->ResultArray(); shuffle($followers);
               $followers = array_slice($followers,0,($this->NumFollowers >= 30 ? 30 : $this->NumFollowers));
               prev($followers);
               foreach ($followers as $user) {
                  ?>
                  <div>
                     <?php echo UserPhoto($user); ?>
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
