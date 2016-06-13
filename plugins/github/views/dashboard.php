<h1>GitHub</h1>

<script>
    $(document).ready(function () {

        $("#setup-button").click(function () {
            $("#setup").toggle();
        });

        $("#setup-close").click(function () {
            $("#setup").hide();
        });

        $("#enable-button").click(function () {
            window.location='<?php echo $this->Data['ToggleUrl']; ?>';
        });

        $("#disable-button").click(function () {
            window.location='<?php echo $this->Data['ToggleUrl']; ?>';
        });

        $("#connect-button").click(function () {
            window.location='<?php echo Url('/plugin/github/authorize'); ?>';
        });


    });

</script>

<div class="Info">
    <?php echo T('This plugin allows you to submit user discussion and comments as GitHub issues'); ?>.
</div>

<div class="FilterMenu">
    <button id="setup-button" class="Button"><?php echo T('Show Setup Instructions'); ?></button>
</div>

<div style="display: none" id="setup">

    <h3><?php echo T('Setup Instructions'); ?></h3>

    <div class="Info">
        <?php echo T('If you already have an account you need to enable API Access for this plugin to work.'); ?>

        <ol>
            <li><?php
                echo Anchor(
                    'Create a new application in GitHub',
                    'https://github.com/settings/applications/new',
                    '',
                    array('Target' => '_blank')
                 );
                ?>
            <ul><li>Callback Url: <?php echo Url('/profile/githubconnect', true); ?></li></ul>
            <li><?php echo T('Enter the Client ID below'); ?></li>
            <li><?php echo T('Enter the Secret below'); ?></li>
            <li><?php echo T('Enter the repositories you want to be allowed'); ?></li>
        </ol>

    </div>

    <div class="Buttons Wrap">
        <button id="setup-close" class="Button"><?php echo T('Hide Instructions'); ?></button>
    </div>

</div>


<h3><?php echo T('GitHub Settings'); ?></h3>

<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>

    <li>
        <?php
        echo $this->Form->Label('ClientID', 'ApplicationID');
        echo $this->Form->TextBox('ApplicationID');
        ?>
    </li>

    <li>
        <?php
        echo $this->Form->Label('Secret', 'Secret');
        echo $this->Form->TextBox('Secret');
        ?>
    </li>

    <li>
        <div class="label-wrap">
            <?php echo $this->Form->Label('Repositories', 'Repositories'); ?>
            <div class="Info"><?php echo T('List of Repositories separted by newline.  Enter the GitHub username and repo, E.g. \'username/reponame\''); ?></div>
        </div>
        <?php echo $this->Form->TextBox('Repositories', array('MultiLine' => true)); ?>
    </li>

</ul>

<div class="Buttons Wrap">
    <?php echo $this->Form->Close('Save'); ?>
</div>


<h3 id="global-login">Global Login</h3>
<div class="Info">
    <p><?php echo T('This feature will allow you to have all Staff use one GitHub connection.'); ?></p>

    <p><?php echo T('If a user has a connection already established we will use that instead.'); ?></p>
</div>

<?php if (!$this->Data['GlobalLoginEnabled']) { ?>

    <div class="Info"><?php echo T('Global Login is currently'); ?> <strong><?php echo T('Disabled'); ?></strong></div>

    <button class="Button" id="enable-button"><?php echo T('Enable'); ?></button>

<?php } else { ?>

    <div class="Info">
        <?php echo T('Global login is currently'); ?> <strong><?php echo T('Enabled'); ?></strong>

        <?php if ($this->Data['GlobalLoginConnected']) { ?>

            <div>
                <?php echo T('You are connected as'); ?>
                <strong><?php echo Gdn_Format::Html($this->Data['GlobalLoginProfile']['fullname']); ?></strong>
            </div>

        <?php } ?>

    </div>


    <?php if (!$this->Data['GlobalLoginConnected']) { ?>

        <button class="Button" id="connect-button"><?php echo T('Connect'); ?></button>

    <?php } ?>

    <button class="Button" id="disable-button"><?php echo T('Disable'); ?></button>

<?php } ?>
