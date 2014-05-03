<script>
    $(document).ready(function () {

        $("#setup-button").click(function () {
            $("#setup").toggle();
        });

        $("#setup-close").click(function () {
            $("#setup").hide();
        });


    });

</script>

<h1><?php echo T($this->Data['Title']); ?></h1>

<div class="Info">
    <?php echo T('This plugin allows you to submit user discussion and comments to your hosted Zendesk'); ?>
</div>

<div class="FilterMenu">
    <button id="setup-button" class="Button">Show Setup Instructions</button>
</div>

<div style="display: none" id="setup">

    <h3>Setup Instructions</h3>

    <div class="Info">
        If you already have an account you need to enable API Access for this plugin to work

        <ul>
            <li>Login to your Zendesk Site</li>
            <li>Go to the <strong>Admin</strong> Setting</li>
            <li>Under <strong>Channels</strong> Select API</li>
            <li>Select the <strong>OAuth Clients</strong></li>
            <li>Add a client</li>
            <li>Complete the form</li>
            <li>Copy the <strong>Unique Identifier</strong> and <strong>Secret</strong> and enter it below</li>
            <li>
                Enter the following URLs in the Redirect Urls <br/>
                <strong>
                    &middot; <?php echo Gdn::Request()->Url('/profile/zendeskconnect', true, true, true) ?> <br/>
                    &middot; <?php echo Gdn::Request()->Url('/profile/zendesk/connect', true, true, true) ?>
                </strong>
            </li>
        </ul>

        If you don't have an account you can create one for free at <a href="http://www.zendesk.com/" target="_blank">Zendesk</a>
    </div>

    <div class="Buttons Wrap">
        <button id="setup-close" class="Button">Hide Instructions</button>
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


<h3 id="reconnect">Global Login</h3>
<div class="Info">
    <p>This feature will allow you to have all Staff use one Zendesk Connection.</p>

    <p>If a user has a connection already established we will use that instead.</p>
</div>
<?php if (!$this->Data['GlobalLoginEnabled']) { ?>
    <div class="Info">Global Login is currently <strong>Disabled</strong></div>

    <button class="Button" onclick="window.location='<?php echo $this->Data['ToggleUrl']; ?>';">Enable</button>
<?php } else { ?>

    <div class="Info">
        Global Login is currently <strong>Enabled</strong>
    </div>

    <?php if (!$this->Data['GlobalLoginConnected']) { ?>

        <button class="Button" onclick="window.location='/plugin/zendesk/authorize';">Connect</button>

    <?php } ?>

    <button class="Button" onclick="window.location='<?php echo $this->Data['ToggleUrl']; ?>';">Disable</button>

<?php } ?>
