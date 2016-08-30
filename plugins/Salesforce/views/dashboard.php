<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t($this->Data['Title']); ?></h1>
<div class="padded alert alert-warning">
    <?php echo sprintf(t('You must register your application with %s for this plugin to work.'), t('Salesforce')); ?>
</div>
<div class="padded">
    <?php echo t('Connects to a Salesforce account. Once connected staff users will be able to create leads and cases from discussions and comments.'); ?>
</div>

<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
    <li class="form-group row">
        <?php
        echo $this->Form->labelWrap('ApplicationID', 'Plugins.Salesforce.ApplicationID');
        echo $this->Form->textBoxWrap('Plugins.Salesforce.ApplicationID');
        ?>
    </li>
    <li class="form-group row">
        <?php
        echo $this->Form->labelWrap('Secret', 'Plugins.Salesforce.Secret');
        echo $this->Form->textBoxWrap('Plugins.Salesforce.Secret');
        ?>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
            <?php echo $this->Form->label('Authentication URL', 'Plugins.Salesforce.AuthenticationUrl'); ?>
            <div class="info">
                <?php echo t('Default: https://login.salesforce.com'); ?>
            </div>
        </div>
        <?php echo $this->Form->textBoxWrap('Plugins.Salesforce.AuthenticationUrl'); ?>
    </li>
</ul>

<div class="form-footer js-modal-footer">
    <?php echo $this->Form->Close('Save'); ?>
</div>

<div class="form-group row">
    <div class="label-wrap-wide">
        <div class="label-title"><?php echo t('Global Login'); ?>
            <span class="text-success">
                <?php if (isset($this->Data['DashboardConnectionProfile']['fullname'])) { ?>
                    <?php echo T('You are connected as'); ?>
                    <strong><?php echo Gdn_Format::Html($this->Data['DashboardConnectionProfile']['fullname']); ?></strong>
                <?php } ?>
            </span></div>
        <div class="info">
            <?php echo t('This feature will allow you to have all Staff use one Salesforce connection.')
                    .' '.t('If a user has a Salesforce connection already established we will use that instead.') ?>
            <span class="text-danger"><?php echo t('Note that all Leads and Cases created will show that they have been created by this user.'); ?></span>
        </div>
    </div>
    <?php if (!$this->Data['DashboardConnection']) { ?>
        <div class="input-wrap-right">
            <a class="btn btn-primary" href="<?php echo url('/plugin/Salesforce/enable'); ?>"><?php echo T('Enable'); ?></a>
        </div>
    <?php } else { ?>
        <div class="input-wrap-right">
            <a class="btn btn-primary" href="<?php echo url('/plugin/Salesforce/connect'); ?>"><?php echo T('Connect'); ?></a>
            <a class="btn btn-primary" href="<?php echo url('/plugin/Salesforce/disable'); ?>"><?php echo T('Disable'); ?></a>
        </div>
    <?php } ?>
</div>
