<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$RoleCss = strtolower(implode(' role-', $this->_Sender->Roles));
if ($RoleCss != '')
   $RoleCss = ' role-'.$RoleCss;

$Photo = $this->User->Photo != '' ? Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')) : 'themes/pennyarcade/design/images/profile_user.png';
$Jailed = $this->User->Jailed == '1' ? Img('themes/pennyarcade/design/images/jailed-180.png', array('alt' => 'Jailed', 'class' => 'Jailed')) : '';
?>
<div class="Box PhotoBox">
   <h4>Profile</h4>
   <div class="ProfileUser<?php echo $RoleCss; ?>">
      <?php
      echo Wrap($this->User->Name, 'div', array('class' => 'Name'));
      echo Wrap('<span class="Rank"></span>'.$Jailed.Img($Photo), 'div', array('class' => 'Photo'));
      if ($this->User->About != '') {
         echo '<div class="Status" id="Status">';
         echo Gdn_Format::Display($this->User->About);
         if ($Session->UserID == $this->User->UserID || $Session->CheckPermission('Garden.Users.Edit'))
            echo ' - ' . Anchor(T('Clear'), '/profile/clear/'.$this->User->UserID.'/'.$Session->TransientKey(), 'Change');
         echo '</div>';
      }
      ?>
   </div>
</div>