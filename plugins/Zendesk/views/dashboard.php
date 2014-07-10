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
            window.location='<?php echo Url('/plugin/zendesk/authorize'); ?>';
        });


    });

</script>

<h1><?php echo T($this->Data['Title']); ?></h1>

<div class="Info">
    <?php echo T('This plugin allows you to submit user discussion and comments to your hosted Zendesk'); ?>.
</div>

<div class="FilterMenu">
    <button id="setup-button" class="Button"><?php echo T('Show Setup Instructions'); ?></button>
</div>

<div style="display: none" id="setup">

    <h3><?php echo T('Setup Instructions'); ?></h3>

    <div class="Info">
        <?php echo T('If you already have an account you need to enable API Access for this plugin to work'); ?>

        <ul>
            <li><?php echo T('Login to your Zendesk Site'); ?></li>
            <li><?php echo T('Go to the'); ?> <strong><?php echo T('Admin'); ?></strong> <?php echo T('Setting'); ?></li>
            <li><?php echo T('Under'); ?> <strong>Channels</strong> <?php echo T('Select API'); ?></li>
            <li><?php echo T('Select the'); ?> <strong><?php echo T('OAuth Clients'); ?></strong></li>
            <li><?php echo T('Add a client'); ?></li>
            <li><?php echo T('Complete the form'); ?></li>
            <li><?php echo T('Copy the'); ?> <strong><?php echo T('Unique Identifier'); ?></strong> <?php echo T('and'); ?> <strong>
                    <?php echo T('Secret'); ?></strong> <?php echo T('and enter it below'); ?></li>
            <li>
                <?php echo T('Enter the following URLs in the Redirect Urls'); ?> <br/>
                <strong>
                    &middot; <?php echo Gdn::Request()->Url('/profile/zendeskconnect', true, true, true) ?> <br/>
                    &middot; <?php echo Gdn::Request()->Url('/plugin/zendesk/connect', true, true, true) ?>
                </strong>
            </li>
        </ul>

        <?php echo T('If you don\'t have an account you can create one for free at'); ?>' <a href="http://www.zendesk.com/" target="_blank">Zendesk</a>
    </div>

    <div class="Buttons Wrap">
        <button id="setup-close" class="Button"><?php echo T('Hide Instructions'); ?></button>
    </div>

</div>


<h3><?php echo T('Zendesk Settings'); ?></h3>

<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>

    <li>
        <?php
        echo $this->Form->Label('Your Zendesk URL', 'Url');
        echo $this->Form->TextBox('Url');
        ?>
        <span>ex. https://example.zendesk.com</span>
    </li>

    <li>
        <?php
        echo $this->Form->Label('Unique Identifier', 'ApplicationID');
        echo $this->Form->TextBox('ApplicationID');
        ?>
    </li>

    <li>
        <?php
        echo $this->Form->Label('Secret', 'Secret');
        echo $this->Form->TextBox('Secret');
        ?>
    </li>


</ul>

<div class="Buttons Wrap">
    <?php echo $this->Form->Close('Save'); ?>
</div>


<h3 id="global-login">Global Login</h3>
<div class="Info">
    <p><?php echo T('This feature will allow you to have all Staff use one Zendesk Connection.'); ?></p>

    <p><?php echo T('If a user has a connection already established we will use that instead.'); ?></p>
</div>
<?php if (!$this->Data['GlobalLoginEnabled']) { ?>
    <div class="Info"><?php echo T('Global Login is currently'); ?> <strong><?php echo T('Disabled'); ?></strong></div>

    <button class="Button" id="enable-button"><?php echo T('Enable'); ?></button>

<?php } else { ?>

    <div class="Info">
        <?php echo T('Global Login is currently'); ?> <strong><?php echo T('Enabled'); ?></strong>

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
