<?php if (!defined('APPLICATION')) exit();

class FollowingModule extends Gdn_Module {
   
   protected $Followees;
   protected $NumFollowees;
   
   protected $Followers;
   protected $NumFollowers;
   
   public function __construct($Sender = '') {
      parent::__construct($Sender);
   }
   
   public function SetUser($UserID) {
      $this->NumFollowees = Gdn::SQL()
         ->Select('f.UserID', 'Count', 'NumUsers')
         ->From('Following f')
         ->Where('f.UserID', $UserID)->Get()->Value('NumUsers', 0);
      $this->Followees = Gdn::SQL()
         ->Select('u.UserID, u.Name, u.Photo')
         ->From('Following f')
         ->Join('User u', 'u.UserID = f.FollowedUserID')
         ->Where('f.UserID', $UserID)
         ->Where('u.Photo IS NOT NULL')
         ->Get();
            
      $this->NumFollowers = Gdn::SQL()
         ->Select('f.UserID', 'Count', 'NumUsers')
         ->From('Following f')
         ->Where('f.FollowedUserID', $UserID)->Get()->Value('NumUsers', 0);
      $this->Followers = Gdn::SQL()
         ->Select('u.UserID, u.Name, u.Photo')
         ->From('Following f')
         ->Join('User u', 'u.UserID = f.UserID')
         ->Where('f.FollowedUserID', $UserID)
         ->Where('u.Photo IS NOT NULL')
         ->Get();
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      $String = '';
      ob_start();
      ?>
      <div id="FollowingPluginUsers" class="Box">
         <h4><?php echo T("Friends"); ?></h4>
         <?php
         if ($this->NumFollowees) {
            echo "<div class=\"\">".sprintf(Plural($this->NumFollowees, 'Following %d person', 'Following %d people'), $this->NumFollowees)."</div>\n";
            if ($this->Followees->NumRows() > 0) {
               echo '<div class="FriendsList">';
               $Followees = $this->Followees->ResultArray(); shuffle($Followees);
               $Followees = array_slice($Followees,0,($this->NumFollowees >= 30 ? 30 : $this->NumFollowees));
               foreach ($Followees as $User) {
                  ?>
                  <div>
                     <a title="<?php echo $User['Name']; ?>" href="<?php echo Url("profile/{$User['UserID']}/{$User['Name']}", TRUE); ?>">
                        <img src="<?php echo Url('uploads'.DS.'n'.$User['Photo'], TRUE); ?>" />
                     </a>
                  </div>
                  <?php
               }
               unset($Followees); unset($this->Followees);
               echo '</div>';
            }
         }
         
         if ($this->NumFollowers) {
            echo "<div class=\"\">".sprintf(Plural($this->NumFollowers, 'Followed by %d person', 'Followed by %d people'), $this->NumFollowers)."</div>\n";
            if ($this->Followers->NumRows() > 0) {
               echo '<div class="FriendsList">';
               $Followers = $this->Followers->ResultArray(); shuffle($Followers);
               $Followers = array_slice($Followers,0,($this->NumFollowers >= 30 ? 30 : $this->NumFollowers));
               prev($Followers);
               foreach ($Followers as $User) {
                  ?>
                  <div>
                     <?php echo UserPhoto($User); ?>
                  </div>
                  <?php
               }
               unset($Followers); unset($this->Followers);
               echo '</div>';
            }
         }
         ?>
      </div>
      <?php
      $String = ob_get_contents();
      @ob_end_clean();
      return $String;
   }
}
