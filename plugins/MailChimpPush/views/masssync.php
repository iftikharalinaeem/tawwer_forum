<div class="header-menu">
    <a class="header-menu-item active" role="heading" aria-level="1" href="<?php echo url('/plugin/mailchimp'); ?>"><?php echo t('MailChimp Settings'); ?></a>
    <a class="header-menu-item" href="<?php echo url('/plugin/mailchimp/masssync'); ?>"><?php echo t('Mass Synchronization'); ?></a>
</div>
<div class="InfoRow MailChimpSync">
    <?php if (!defined('APPLICATION')) exit();
    echo $this->Sync->open();
    echo $this->Sync->errors();
    ?>

    <h2><?php echo t('Mass Synchronization'); ?></h2>
    <div class="alert alert-info padded" id="SychronizationMessages">
        <?php echo t('About MailChimpPush Synchronization', "By default, Vanilla only sends <b>changes</b> to MailChimp. Synchronization
      is a one-time action that allows an entire forum's worth of users email
      addresses to be pushed to MailChimp to populate a list."); ?>
    </div>

    <div class="Synchronization">
        <div class="SyncProgressTitle label padded-bottom"><?php echo t('Processing Data'); ?><span></span></div>
        <div class="SyncBar"><div class="SyncProgress"></div></div>
    </div>
    <ul class="SyncList">
        <li class="form-group">
            <?php echo $this->Sync->labelWrap("Sync to List", "SyncListID"); ?>
            <div class="input-wrap">
                <?php echo $this->Sync->dropDown('SyncListID', $this->data('Lists'), array('IncludeNull' => true)); ?>
            </div>
        </li>
        <?php
        // Create any dropdowns of interests associated with lists, each dropdown is hidden
        // by javascript unless the list is selected.
        foreach ($interests as $list => $interest) {
            echo "<li id='SyncInterestDropdown{$list}' class='SyncInterestDropdowns form-group'>";
            echo $this->Sync->labelWrap('Interest', 'SyncInterestID'.$list);
            echo '<div class="input-wrap">';
            // Disable the sync interest dropdown by default. Will be activated by javascript if needed.
            echo $this->Sync->dropDown('SyncInterestID['.$list.']', $interest, array('IncludeNull' => true, 'disabled' => true));
            echo '</div>';
            echo "</li>";
        }
        ?>
        <li class="form-group">
            <div class="input-wrap no-label">
                <?php echo $this->Sync->checkBox('SyncConfirmJoin', 'Send confirmation email?'); ?>
            </div>
        </li>
        <li class="form-group">
            <?php echo $this->Sync->labelWrap("User Selection"); ?>
            <div class="input-wrap">
                <?php
                echo $this->Sync->checkBox('SyncBanned', 'Sync banned users');
                echo $this->Sync->checkBox('SyncDeleted', 'Sync deleted users');

                if ($this->data('ConfirmEmail', false)) {
                    echo $this->Sync->checkBox('SyncUnconfirmed', 'Sync users with unconfirmed email addreses');
                } ?>
            </div>
        </li>
    </ul>

    <div class="form-footer padded-bottom">
        <?php echo $this->Sync->button('Synchronize', array(
            'class' => 'Button',
            'type' => 'button',
            'id' => 'MailChimp-Synchronize'
        )); ?>
    </div>

<?php echo $this->Sync->close(); ?>
