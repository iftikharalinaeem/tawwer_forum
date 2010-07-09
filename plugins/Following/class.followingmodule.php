<?php if (!defined('APPLICATION')) exit();

class FollowingModule extends Gdn_Module {
   
   protected $Followees;
   protected $NumFollowees;
   
   protected $Followers;
   protected $NumFollowers;
   
   public function __construct(&$Sender = '') {
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
               while ($User = $this->Followees->NextRow(DATASET_TYPE_ARRAY)) {
                  ?>
                  <div>
                     <a title="<?php echo $User['Name']; ?>" href="<?php echo Url("profile/{$User['UserID']}/{$User['Name']}", TRUE); ?>">
                        <img src="<?php echo Url('uploads'.DS.'n'.$User['Photo'], TRUE); ?>" />
                     </a>
                  </div>
                  <?php
               }
               echo '</div>';
            }
         }
         
         if ($this->NumFollowers) {
            echo "<div class=\"\">".sprintf(Plural($this->NumFollowers, 'Followed by %d person', 'Followed by %d people'), $this->NumFollowers)."</div>\n";
            if ($this->Followers->NumRows() > 0) {
               echo '<div class="FriendsList">';
               while ($User = $this->Followers->NextRow(DATASET_TYPE_ARRAY)) {
                  ?>
                  <div>
                     <a title="<?php echo $User['Name']; ?>" href="<?php echo Url("profile/{$User['UserID']}/{$User['Name']}", TRUE); ?>">
                        <img src="<?php echo Url('uploads'.DS.'n'.$User['Photo'], TRUE); ?>" />
                     </a>
                  </div>
                  <?php
               }
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
