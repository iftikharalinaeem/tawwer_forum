<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$CssRoles = $this->_Sender->Roles;
foreach ($CssRoles as &$RawRole)
   $RawRole = 'role-'.str_replace(' ','_',  strtolower($RawRole));
$RoleCss = ' '.implode(' ',$CssRoles);
   
$Jailed = '';
$Photo = '';

if ($RoleCss != '')
   $RoleCss = ' role-'.$RoleCss;
   
if ($this->User->Banned == '1') {
   $Photo = 'themes/pennyarcade/design/images/banned-180.png';
   $RoleCss .= ' Banned';
} else {
   if ($this->User->Photo) {
      if (StringBeginsWith($this->User->Photo, 'http')) {
         $Photo = $this->User->Photo;
      } else {
         $Photo = Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s'));
      }
   } elseif (function_exists('UserPhotoDefaultUrl'))
      $Photo = UserPhotoDefaultUrl($this->User);
   else
      $Photo = 'themes/pennyarcade/design/images/profile_user.png';
   
   if ($this->User->Jailed == '1') {
      $RoleCss .= ' Jailed';
      $Jailed = Img('themes/pennyarcade/design/images/jailed-180.png', array('alt' => 'Jailed', 'class' => 'Jailed'));
   }
}
?>
<div class="Box PhotoBox">
   <h4>Profile</h4>
   <div class="ProfileUser<?php echo $RoleCss; ?>">
      <?php
      echo Wrap($this->User->Name, 'div', array('class' => 'Name'));
      echo Wrap('<span class="Rank"></span>'.$Jailed.Img($Photo), 'div', array('class' => 'Photo'));
      echo '<div class="PhotoInfo">';
      
      if ($this->User->About != '') {
         echo '<div class="Status" id="Status">';
         echo Gdn_Format::Display($this->User->About);
         if ($Session->UserID == $this->User->UserID || $Session->CheckPermission('Garden.Users.Edit'))
            echo ' - ' . Anchor(T('Clear'), '/profile/clear/'.$this->User->UserID.'/'.$Session->TransientKey(), 'Change');
         echo '</div>';
      }
      $CustomProfileFields = GetValue('CustomProfileFields', $this->User->Attributes, array());
      $Title = GetValue('Title', $CustomProfileFields);
      $Location = GetValue('Location', $CustomProfileFields);
      if ($Title) {
         echo '<div class="User-Title">'.htmlspecialchars($Title).'</div>';
      }
      if ($Location) {
         echo '<div class="User-Location">'.htmlspecialchars($Location).'</div>';
      }
      echo '</div>';
      ?>
   </div>
</div>