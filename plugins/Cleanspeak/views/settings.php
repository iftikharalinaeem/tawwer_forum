<h1>Cleanspeak</h1>
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


<?php if (!$this->Data['Enabled'] && !$this->Data['IsConfigured']) { ?>

    <div class="Wrap Warning"><?php echo T('Your Cleanspeak Integration is NOT complete.  Enabling the plugin before it has
been configured will force all new content to go into the premoderation queue.'); ?>
    </div>

<?php } ?>


<div class="Wrap">
    <a id="cstoggle" class="Button Hijack" href="<?php echo Url('/settings/cleanspeaktoggle'); ?>">
        <?php
        if($this->Data['Enabled']) {
            echo T('Disable');
        } else {
            echo T('Enable');
        }
        ?>
    </a>
    <span class="Wrap"><?php echo T('Send new discussions, comments, activity posts and comments to Cleanspeak for premoderation.'); ?></span>

</div>


<div class="Info">
    <button id="setup-button" class="Button"><?php echo T('Show Setup Instructions'); ?></button>
</div>

<div style="display: none" id="setup">

    <h3><?php echo T('Setup Instructions'); ?></h3>

    <div class="Info">
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

    <div class="Buttons Wrap">
        <button id="setup-close" class="Button"><?php echo T('Hide Instructions'); ?></button>
    </div>

</div>


<h3><?php echo T('Settings'); ?></h3>

<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<ul>

    <li>
        <?php
        echo $this->Form->Label('Cleanspeak API Url', 'ApiUrl');
        echo $this->Form->TextBox('ApiUrl');
        ?>
    </li>

    <li>
        <?php
        echo $this->Form->Label('Application ID', 'ApplicationID');
        echo $this->Form->TextBox('ApplicationID');
        ?>
    </li>

</ul>

</ul>
<?php
echo $this->Form->Close('Save');
?>
<br />

