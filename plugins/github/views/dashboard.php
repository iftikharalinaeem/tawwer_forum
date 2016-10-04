<h1>GitHub</h1>
<div class="padded alert alert-warning">
    <?php echo sprintf(t('You must register your application with %s for this plugin to work.'), t('GitHub')); ?>
</div>
<div class="padded">
    <?php echo t('This plugin allows you to submit user discussion and comments as GitHub issues.'); ?>
    <?php echo ' '.anchor(sprintf(t('How to set up %s.'), t('GitHub Connect')), 'http://docs.vanillaforums.com/addons/social/github/', array('target' => '_blank')); ?>
</div>

<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
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
            <?php echo $this->Form->Label('Repositories', 'Repositories'); ?>
            <div class="info"><?php echo T('List of Repositories separted by newline.  Enter the GitHub username and repo, E.g. \'username/reponame\''); ?></div>
        </div>
        <?php echo $this->Form->textBoxWrap('Repositories', array('MultiLine' => true)); ?>
    </li>

</ul>

<div class="form-footer js-modal-footer">
    <?php echo $this->Form->Close('Save'); ?>
</div>
<div class="form-group">
    <div class="label-wrap-wide">
        <div class="label"><?php echo t('Global Login'); ?>
            <span class="text-success">
                <?php if ($this->Data['GlobalLoginConnected']) { ?>
                    <?php echo T('You are connected as'); ?>
                        <strong><?php echo Gdn_Format::Html($this->Data['GlobalLoginProfile']['fullname']); ?></strong>
                <?php } ?>
            </span></div>
        <div class="info">
            <p><?php echo t('This feature will allow you to have all Staff use one GitHub connection.')
                .' '.t('If a user has a connection already established we will use that instead.'); ?></p>
        </div>
    </div>
<?php if (!$this->Data['GlobalLoginEnabled']) { ?>
    <div class="input-wrap-right">
        <a class="btn btn-primary" href="<?php echo $this->Data['ToggleUrl']; ?>"><?php echo T('Enable'); ?></a>
    </div>
<?php } else { ?>
    <div class="input-wrap-right">
        <?php if (!$this->Data['GlobalLoginConnected']) { ?>
            <a class="btn btn-primary" href="<?php echo url('/plugin/github/authorize'); ?>"><?php echo T('Connect'); ?></a>
        <?php } ?>
        <a class="btn btn-primary" href="<?php echo $this->Data['ToggleUrl']; ?>"><?php echo T('Disable'); ?></a>
    </div>
<?php } ?>
</div>
