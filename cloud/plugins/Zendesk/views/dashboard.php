<h1><?php echo t($this->Data['Title']); ?></h1>
<div class="padded alert alert-warning">
    <?php echo sprintf(t('You must register your application with %s for this plugin to work.'), t('Zendesk')); ?>
</div>
<div class="padded">
    <?php echo t('This plugin allows you to submit user discussion and comments to your hosted Zendesk.'); ?>
    <?php echo ' '.anchor(sprintf(t('How to set up %s.'), t('Zendesk')), 'http://docs.vanillaforums.com/help/addons/zendesk/', ['target' => '_blank']); ?>
</div>

<?php
// Settings
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>

    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Your Zendesk URL', 'Url'); ?>
            <div class="info">
                <?php echo t('ex. https://example.zendesk.com'); ?>
            </div>
        </div>
        <?php echo $this->Form->textBoxWrap('Url'); ?>
    </li>

    <li class="form-group">
        <?php
        echo $this->Form->labelWrap('Unique Identifier', 'ApplicationID');
        echo $this->Form->textBoxWrap('ApplicationID');
        ?>
    </li>

    <li class="form-group">
        <?php
        echo $this->Form->labelWrap('Secret', 'Secret');
        echo $this->Form->textBoxWrap('Secret');
        ?>
    </li>
</ul>

<?php echo $this->Form->close('Save'); ?>

<div class="form-group">
    <div class="label-wrap-wide">
        <?php if ($this->Data['GlobalLoginConnected']) { ?>
        <div class="label"><?php echo t('Global Login'); ?>
            <span class="text-success">
                    <?php echo t('You are connected as'); ?>
                    <strong><?php echo Gdn_Format::html($this->Data['GlobalLoginProfile']['fullname']); ?></strong>
            </span></div>
        <div class="info">
            <p><?php echo t('This feature will allow you to have all Staff use one Zendesk connection.')
                    .' '.t('If a user has a connection already established we will use that instead.'); ?></p>
        </div>
        <?php } elseif ($this->Data['GlobalLoginError']) {?>
        <div class="label text-danger"><?php echo t('Error connecting to Zendesk:'); ?></div>
            <span class="text-warning">
                <?php echo Gdn_Format::html($this->Data['GlobalLoginError']['message']); ?>
            </span>
        <?php } ?>
    </div>

    <?php if (!$this->Data['GlobalLoginEnabled']) { ?>
        <div class="input-wrap-right">
            <a class="btn btn-primary" href="<?php echo $this->Data['ToggleUrl']; ?>"><?php echo t('Enable'); ?></a>
        </div>
    <?php } else { ?>
        <div class="input-wrap-right">
            <?php if (!$this->Data['GlobalLoginConnected']) { ?>
                <a class="btn btn-primary" href="<?php echo url('/plugin/zendesk/authorize'); ?>"><?php echo t('Connect'); ?></a>
            <?php } ?>
            <a class="btn btn-primary" href="<?php echo $this->Data['ToggleUrl']; ?>"><?php echo t('Disable'); ?></a>
        </div>
    <?php } ?>
</div>
