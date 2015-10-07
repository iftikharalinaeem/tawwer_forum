<?php
$isEnded = EventModel::isEnded($this->Data('Event'));
?>
<div class="AttendeeList YesAttending"><?php
   $Yes = sizeof($this->Data('Invited.Yes', array()));
   if ($isEnded && $Yes) {
      echo Wrap(sprintf(T('Attended (%s)'), $Yes), 'h3');
   }
   if (!$isEnded) {
      echo Wrap(sprintf(T('Attending (%s)'), $Yes), 'h3');
      if (!$Yes) {
         echo T("Nobody has confirmed their attendance yet.");
      }
   }
   if ($Yes) {
      foreach ($this->Data('Invited.Yes') as $Invitee) {
         echo UserPhoto($Invitee);
      }
   }
?></div>

<div class="Negatives">
   <div class="AttendeeList NotAttending"><?php
      $No = sizeof($this->Data('Invited.No', array()));
      if ($isEnded && $No) {
         echo Wrap(sprintf(T('Did not attend (%s)'), $No), 'h3');
      }
      if (!$isEnded) {
         echo Wrap(sprintf(T('Not Attending (%s)'), $No),'h3');
         if (!$No) {
            echo T("Nobody has declined the invitation so far.");
         }
      }
      if ($No) {
         foreach ($this->Data('Invited.No') as $Invitee) {
            echo UserPhoto($Invitee);
         }
      }
     ?>
   </div>
   <div class="AttendeeList MaybeAttending">
      <?php
      $Maybe = sizeof($this->Data('Invited.Maybe', array()));
      if ($isEnded && $Maybe) {
         echo Wrap(sprintf(T('Maybe (%s)'), $Maybe), 'h3');
      }
      if (!$isEnded) {
         echo Wrap(sprintf(T('Maybe (%s)'), $Maybe),'h3');
         if (!$Maybe) {
            echo T("Nobody is on the fence right now.");
         }
      }
      if ($Maybe) {
         foreach ($this->Data('Invited.Maybe') as $Invitee) {
            echo UserPhoto($Invitee);
         }
      }
      ?>
   </div>
</div>

<?php
$Invited = sizeof($this->Data('Invited.Invited', array()));
if ($Invited): ?>
<div class="InvitedAttending"><?php echo sprintf(Plural($Invited, '%s unanswered invitation.', '%s unanswered invitations.'), $Invited); ?></div>
<?php endif; ?>
