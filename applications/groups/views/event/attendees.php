<div class="AttendeeList YesAttending"><?php
   $Yes = sizeof($this->Data('Invited.Yes', array()));
   echo Wrap(sprintf(T('Attending (%d)'), $Yes),'h3');

   if (!$Yes)
      echo T("Nobody has confirmed their attendance yet.");
   else
      foreach ($this->Data('Invited.Yes') as $Invitee)
         echo UserPhoto($Invitee);
?></div>

<div class="Negatives">
   <div class="AttendeeList NotAttending"><?php
      $No = sizeof($this->Data('Invited.No', array()));
      echo Wrap(sprintf(T('Not Attending (%d)'), $No),'h3');

      if (!$No)
         echo T("Nobody has declined the invitation so far.");
      else
         foreach ($this->Data('Invited.No') as $Invitee)
            echo UserPhoto($Invitee);
   ?></div>

   <div class="AttendeeList MaybeAttending">
      <?php
         $Maybe = sizeof($this->Data('Invited.Maybe', array()));
         echo Wrap(sprintf(T('Maybe (%d)'), $Maybe),'h3');

         if (!$Maybe)
            echo T("Nobody is on the fence right now.");
         else
            foreach ($this->Data('Invited.Maybe') as $Invitee)
               echo UserPhoto($Invitee);
      ?>
   </div>
</div>

<?php 
$Invited = sizeof($this->Data('Invited.Invited', array()));
if ($Invited): ?>
<div class="InvitedAttending"><?php echo sprintf(Plural($Invited, '%s unanswered invitation.', '%s unanswered invitations.'), $Invited); ?></div>
<?php endif; ?>