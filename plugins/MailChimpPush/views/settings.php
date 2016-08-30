<h1><?php echo $this->data('Title'); ?></h1>

<style class="text/css">
    .MailChimpSync .SyncBar {
        height: 15px;
        background: #8BC8FF;
        border-radius: 4px;
        border: 1px solid #489CF7;
        width: 100%;
    }
    .MailChimpSync .SyncBar .SyncProgress {
        height: 15px;
        background: #489CF7;
        border-radius: 3px;
        width: 0%;
        font-size: 11px;
        text-align: right;
        line-height: 15px;
    }
    .MailChimpSync .SyncBar .SyncProgress span {
        color: white;
        margin-right: 5px;
    }

    /* Error */
    .MailChimpSync .Synchronization.Error {
        background: #FFC0C0;
    }
    .MailChimpSync .Synchronization.Error .SyncProgressTitle span {
        display: none;
    }
    .MailChimpSync .Synchronization.Error .SyncBar {
        border-color: #FA5A5A;
    }
    .MailChimpSync .Synchronization.Error .SyncBar .SyncProgress {
        width: 100%;
        text-align: center;
        background: #FA5A5A;
        color: white;
    }

    /* Finished */
    .MailChimpSync .Synchronization.Finished .SyncBar .SyncProgress {
        width: 100%;
        text-align: center;
        color: white;
    }
    .MailChimpSync .Synchronization.Finished .SyncProgressTitle span {
        display: none;
    }

    .MailChimpSync #MailChimp-Synchronize:disabled {
        opacity: 0.5;
    }
</style>
<div class="InfoRow MailChimpSettings">

    <?php if (!defined('APPLICATION')) exit();
    echo $this->Form->open();
    echo $this->Form->errors();
    ?>

    <div class="padded alert-info alert">
        <?php echo t('About MailChimpPush', "MailChimp Push synchronizes your users'
      email addresses with a MailChimp mailing list of your choice. When a new 
      user signs up, or when an existing user changed their email, Vanilla will 
      send a notification to MailChimp to add or update the user."); ?>
    </div>

    <h2><?php echo t('API Authentication settings'); ?></h2>

    <ul>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->label("API Key", "ApiKey"); ?>
                <div class="info">
                    <?php echo Anchor(t('How to find your MailChimp API key'),
                        'http://kb.mailchimp.com/article/where-can-i-find-my-api-key'); ?>
                </div>
            </div>
            <?php echo $this->Form->textBoxWrap('ApiKey');  ?>
        </li>
    </ul>

    <?php if ($this->data('Configured')): ?>
        <ul class="MailingList">
            <li class="form-group row">
                <div class="label-wrap">
                    <?php echo $this->Form->label("Mailing List", "ListID"); ?>
                    <div class="info">
      ?></li>
      <?php
                        <?php echo t('MailChimpPush List Settings', "Choose which list MailChimp
         // Create any dropdowns of interests associated with lists, each dropdown is hidden
            will synchronize to when new users register, or existing ones change 
            their email address."); ?>
                    </div>
                </div>
                <div class="input-wrap">
                    <?php echo $this->Form->dropDown('ListID', $this->data('Lists'), array('IncludeNull' => TRUE)); ?>
                </div>
            </li>

            <li class="form-group row">
      ?>
                <div class="input-wrap no-label">
                    <?php echo $this->Form->checkBox('ConfirmJoin', 'Send confirmation email?'); ?>
                </div>
            </li>
        </ul>
    <?php endif; ?>
    <div class="form-footer padded-bottom">
        <?php echo $this->Form->button('Save'); ?>
    </div>
    <?php echo $this->Form->close(); ?>
</div>

<?php if ($this->data('Configured')): ?>
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
            <li class="form-group row">
                <?php echo $this->Sync->labelWrap("Sync to List", "SyncListID"); ?>
                <div class="input-wrap">
                    <?php echo $this->Sync->dropDown('SyncListID', $this->data('Lists'), array('IncludeNull' => true)); ?>
                </div>
            </li>
            <li class="form-group row">
                <div class="input-wrap no-label">
                    <?php echo $this->Sync->checkBox('SyncConfirmJoin', 'Send confirmation email?'); ?>
                </div>
            </li>
            <li class="form-group row">
                <?php echo $this->Sync->labelWrap("User Selection"); ?>
                <div class="input-wrap stacked">
                    <?php
                    echo $this->Sync->checkBox('SyncBanned', 'Sync banned users');
                    echo $this->Sync->checkBox('SyncDeleted', 'Sync deleted users');

                    if ($this->data('ConfirmEmail', false)) {
      // by javascript unless the list is selected.
      foreach ($interests as $list => $interest) {
         echo "<li id='SyncInterestDropdown{$list}' class='SyncInterestDropdowns'>";
         echo $this->Sync->label('Interest', 'SyncInterestID'.$list);
         // Disable the sync interest dropdown by default. Will be activated by javascript if needed.
                        echo $this->Sync->checkBox('SyncUnconfirmed', 'Sync users with unconfirmed email addreses');
                    } ?>
      }
      ?>
      <li><?php
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
    </div>
<?php endif; ?>
