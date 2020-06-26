<h1><?php echo $this->data('Title'); ?></h1>

<div class="padded alert alert-warning">
    <?php echo t('Configure the API Key and Auth Domain of your Firebase application, turn on or off the third-party providers you have configured on your Firebase application, for a single sign-on user experience.'); ?>
</div>
<?php
echo $this->Form->open();
echo $this->Form->errors();
echo $this->Form->simple($this->data('_Form'));
echo $this->Form->close('Save');
