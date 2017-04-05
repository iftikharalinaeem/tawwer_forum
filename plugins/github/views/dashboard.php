<h1>GitHub</h1>
<div class="padded alert alert-warning">
    <?php echo sprintf(t('You must register your application with %s for this plugin to work.'), t('GitHub')); ?>
</div>
<div class="padded">
    <?php echo t('This plugin allows you to submit user discussion and comments as GitHub issues.'); ?>
    <?php echo ' '.anchor(sprintf(t('How to set up %s.'), t('GitHub Connect')), 'http://docs.vanillaforums.com/help/addons/social/github/', ['target' => '_blank']); ?>
</div>

<?php
// Settings
echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>

    <li class="form-group">
        <?php
        echo $this->Form->labelWrap('ClientID', 'ApplicationID');
        echo $this->Form->textBoxWrap('ApplicationID');
        ?>
    </li>

    <li class="form-group">
        <?php
        echo $this->Form->labelWrap('Secret', 'Secret');
        echo $this->Form->textBoxWrap('Secret');
        ?>
    </li>

    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Repositories', 'Repositories'); ?>
            <div class="info"><?php echo t('List of Repositories separted by newline.  Enter the GitHub username and repo, E.g. \'username/reponame\''); ?></div>
        </div>
        <?php echo $this->Form->textBoxWrap('Repositories', ['MultiLine' => true]); ?>
    </li>

</ul>
<?php echo $this->Form->close('Save'); ?>

<div class="form-group">
    <div class="label-wrap-wide">
        <div class="label"><?php echo t('Global Login'); ?>
            <span class="text-success">
                <?php if ($this->Data['GlobalLoginConnected']) { ?>
                    <?php echo t('You are connected as'); ?>
                        <strong><?php echo Gdn_Format::html($this->Data['GlobalLoginProfile']['fullname']); ?></strong>
                <?php } ?>
            </span></div>
        <div class="info">
            <p><?php echo t('This feature will allow you to have all Staff use one GitHub connection.')
                .' '.t('If a user has a connection already established we will use that instead.'); ?></p>
        </div>
    </div>
<?php if (!$this->Data['GlobalLoginEnabled']) { ?>
    <div class="input-wrap-right">
        <a class="btn btn-primary" href="<?php echo $this->Data['ToggleUrl']; ?>"><?php echo t('Enable'); ?></a>
    </div>
<?php } else { ?>
    <div class="input-wrap-right">
        <?php if (!$this->Data['GlobalLoginConnected']) { ?>
            <a class="btn btn-primary" href="<?php echo url('/plugin/github/authorize'); ?>"><?php echo t('Connect'); ?></a>
        <?php } ?>
        <a class="btn btn-primary" href="<?php echo $this->Data['ToggleUrl']; ?>"><?php echo t('Disable'); ?></a>
    </div>
<?php } ?>
</div>
