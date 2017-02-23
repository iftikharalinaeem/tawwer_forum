<?php if (!defined('APPLICATION')) exit();
helpAsset(sprintf(t('About %s'), t('Mass Synchronization')),
    t('About MailChimpPush Synchronization', "Synchronization allows an entire forum's worth of users email addresses to be pushed to 
    MailChimp to populate a list. This tool will never update existing users on MailChimp. It will only add users with emails that do 
    not already exist on the user's MailChimp account.")
);
?>
<div class="header-menu">
    <a class="header-menu-item" href="<?php echo url('/plugin/mailchimp'); ?>"><?php echo t('MailChimp Settings'); ?></a>
    <a class="header-menu-item active" role="heading" aria-level="1" href="<?php echo url('/plugin/mailchimp/masssync'); ?>"><?php echo t('Mass Synchronization'); ?></a>
</div>
<div class="InfoRow MailChimpSync">
    <?php
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>
    <div id="SynchronizationMessages" class="alert alert-success padded">
    </div>

    <div class="alert padded Synchronization">
        <div class="SyncProgressTitle label"><span></span></div>
        <div class="SyncBar"><div class="SyncProgress"></div></div>
    </div>

    <ul class="SyncList">
        <li class="form-group">
            <?php echo $this->Form->labelWrap("Sync to List", "SyncListID"); ?>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('SyncListID', $this->data('Lists'), array('IncludeNull' => true)); ?>
            </div>
        </li>
        <?php
        $interests = $this->data('Interests');
        // Create any dropdowns of interests associated with lists, each dropdown is hidden
        // by javascript unless the list is selected.
        foreach ($interests as $list => $interest) {
            echo "<li id='SyncInterestDropdown{$list}' class='SyncInterestDropdowns form-group'>";
            echo $this->Form->labelWrap('Group', 'SyncInterestID'.$list);
            echo '<div class="input-wrap">';
            // Disable the sync interest dropdown by default. Will be activated by javascript if needed.
            echo $this->Form->dropDown('SyncInterestID['.$list.']', $interest, array('IncludeNull' => true, 'disabled' => true));
            echo '</div>';
            echo "</li>";
        }
        ?>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Form->checkBox('SyncConfirmJoin', 'Send confirmation email?'); ?>
            </div>
        </li>
        <li class="form-group">
            <?php echo $this->Form->labelWrap("User Selection"); ?>
            <div class="input-wrap">
                <?php
                echo $this->Form->checkBox('SyncBanned', 'Sync banned users');
                echo $this->Form->checkBox('SyncDeleted', 'Sync deleted users');

                if ($this->data('ConfirmEmail', false)) {
                    echo $this->Form->checkBox('SyncUnconfirmed', 'Sync users with unconfirmed email addreses');
                } ?>
            </div>
        </li>
    </ul>

<?php
echo $this->Form->close('Synchronize', '', ['id' => 'MailChimp-Synchronize']); ?>
