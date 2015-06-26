<?php
if (!$this->Data('Application') || $this->Data('Application.Type') != 'Invitation')
   return;
?>
<div class="DismissMessage InfoMessage GroupUserHeaderModule">
   <div class="Center">
      <?php
      echo sprintf(T("You've been invited to join %s."),
         Anchor(htmlspecialchars($this->Data('Group.Name')), GroupUrl($this->Data('Group'))));
      echo ' '.T('Would you like to accept the invitation to join this group?')
      ?>
      <div class="Buttons">
         <?php
         echo Anchor(T('Yes'), GroupUrl($this->Data('Group'), 'inviteaccept'), 'Button Hijack');
         echo ' ';
         echo Anchor(T('No'), GroupUrl($this->Data('Group'), 'invitedecline'), 'Button Hijack');
         ?>
      </div>
   </div>
</div>
