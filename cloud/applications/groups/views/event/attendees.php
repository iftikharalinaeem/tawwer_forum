<?php if (!defined('APPLICATION')) exit();

$isEnded = EventModel::isEnded($this->data('Event'));
?>
<div class="AttendeeList YesAttending"><?php
    $Yes = sizeof($this->data('Invited.Yes', []));
    if ($isEnded && $Yes) {
        echo wrap(sprintf(t('Attended (%s)'), $Yes), 'h3');
    }
    if (!$isEnded) {
        echo wrap(sprintf(t('Attending (%s)'), $Yes), 'h3');
        if (!$Yes) {
            echo t("Nobody has confirmed their attendance yet.");
        }
    }
    if ($Yes) {
        foreach ($this->data('Invited.Yes') as $Invitee) {
            echo userPhoto($Invitee);
        }
    }
?></div>

<div class="Negatives">
    <div class="AttendeeList NotAttending"><?php
        $No = sizeof($this->data('Invited.No', []));
        if ($isEnded && $No) {
            echo wrap(sprintf(t('Did not attend (%s)'), $No), 'h3');
        }
        if (!$isEnded) {
            echo wrap(sprintf(t('Not Attending (%s)'), $No),'h3');
            if (!$No) {
                echo t("Nobody has declined the invitation so far.");
            }
        }
        if ($No) {
            foreach ($this->data('Invited.No') as $Invitee) {
                echo userPhoto($Invitee);
            }
        }
      ?>
    </div>
    <div class="AttendeeList MaybeAttending">
        <?php
        $Maybe = sizeof($this->data('Invited.Maybe', []));
        if ($isEnded && $Maybe) {
            echo wrap(sprintf(t('Maybe (%s)'), $Maybe), 'h3');
        }
        if (!$isEnded) {
            echo wrap(sprintf(t('Maybe (%s)'), $Maybe),'h3');
            if (!$Maybe) {
                echo t("Nobody is on the fence right now.");
            }
        }
        if ($Maybe) {
            foreach ($this->data('Invited.Maybe') as $Invitee) {
                echo userPhoto($Invitee);
            }
        }
        ?>
    </div>
</div>

<?php
$Invited = sizeof($this->data('Invited.Invited', []));
if ($Invited): ?>
<div class="InvitedAttending"><?php echo sprintf(plural($Invited, '%s unanswered invitation.', '%s unanswered invitations.'), $Invited); ?></div>
<?php endif; ?>
