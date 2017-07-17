<h1><?php echo sprintf(t('%s Settings'), t('Cleanspeak')); ?></h1>

<?php if (!$this->Data['IsConfigured']) { ?>

    <div class="alert alert-danger padded"><?php echo T('Your Cleanspeak Integration is NOT complete.  Enabling the plugin before it has
been configured will force all new content to go into the premoderation queue.'); ?>
    </div>

<?php } ?>

<div class="padded list-un-reset">
    <h3><?php echo T('Setup Instructions'); ?></h3>

    <ol>
        <li><?php echo T('Complete the form below.'); ?></li>
        <li><?php echo T('Add a new notification server to Cleanspeak.'); ?>
            <ul>
                <li>URL: <?php echo $this->Data['PostBackURL']; ?></li>
                <li><?php echo T('Select the application(s) you want notifications from.'); ?></li>
            </ul>
        </li>
        <li><?php echo T('Enable the plugin.'); ?></li>
    </ol>
</div>


<div class="form-group">
    <div class="label-wrap-wide">
        <div class="label"><?php echo t('Enable Cleanspeak'); ?></div>
        <div class="info"><?php echo t('Send new discussions, comments, activity posts and comments to Cleanspeak for premoderation'); ?></div>
    </div>
    <div class="input-wrap-right">
        <div id="cstoggle">
            <?php
            if($this->Data['Enabled']) {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/cleanspeaktoggle', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
            } else {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/settings/cleanspeaktoggle', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
            }
            ?>
        </div>
    </div>
</div>

<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<ul>
    <li class="form-group">
        <?php
        echo $this->Form->labelWrap('Cleanspeak API URL', 'ApiUrl');
        echo $this->Form->textBoxWrap('ApiUrl');
        ?>
    </li>

    <li class="form-group">
        <?php
        echo $this->Form->labelWrap('Application ID', 'ApplicationID');
        echo $this->Form->textBoxWrap('ApplicationID');
        ?>
    </li>

    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Authentication Key', 'AccessToken'); ?>
            <div class="info">
                <?php echo t('If the Cleanspeak API URL is publicly accessible, authentication key must be set.'); ?>
            </div>
        </div>
        <?php echo $this->Form->textBoxWrap('AccessToken'); ?>
    </li>

</ul>
<?php echo $this->Form->Close('Save'); ?>
