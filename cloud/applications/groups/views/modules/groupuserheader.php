<?php if (!defined('APPLICATION')) exit();

if (!$this->data('Application') || $this->data('Application.Type') != 'Invitation') {
    return;
}
?>
<div class="DismissMessage InfoMessage GroupUserHeaderModule">
    <div class="Center">
        <?php
        echo sprintf(t("You've been invited to join %s.", "You've been invited to join %s. Would you like to accept the invitation to join this group?"),
            anchor(htmlspecialchars($this->data('Group.Name')), groupUrl($this->data('Group'))));
        ?>
        <div class="Buttons">
            <?php
            echo anchor(t('Yes'), groupUrl($this->data('Group'), 'inviteaccept'), 'Button Hijack');
            echo ' ';
            echo anchor(t('No'), groupUrl($this->data('Group'), 'invitedecline'), 'Button Hijack');
            ?>
        </div>
    </div>
</div>
