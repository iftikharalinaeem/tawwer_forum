<?php if (!defined('APPLICATION')) exit();
echo $this->Form->open();
echo $this->Form->errors();
?>
<h1><?php echo T("Online Settings"); ?></h1>
<h2>Placement and Visual Settings</h2>
<ul>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label("Where should the Online list be displayed?", "Plugins.Online.Location"); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->dropDown('Plugins.Online.Location', array(
                'every'           => "On every page",
                'discussionlists' => "Only on Discussion and Category lists",
                'discussions'     => "On all discussion pages",
                'custom'          => "I'll place it manually with my theme"
            ));
            ?>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label("How should the list be rendered?", "Plugins.Online.Style"); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->dropDown('Plugins.Online.Style', array(
                'pictures'        => "User Icons",
                'links'           => "User Links"
            ));
            ?>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label("Hide the Online list from guests?", "Plugins.Online.HideForGuests"); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->dropDown('Plugins.Online.HideForGuests', array(
                'true'            => "Yes, only show to logged-in members",
                'false'           => "No, anyone may view the list"
            ));
            ?>
        </div>
    </li>
</ul>

<h2>Internal Settings</h2>
<ul>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label("How long are you 'online' for after you visit a page?", "Plugins.Online.PruneDelay"); ?>
        </div>
        <div class="input-wrap">
            <?php echo $this->Form->dropDown('Plugins.Online.PruneDelay', array(
                '5'               => '5 minutes',
                '10'              => '10 minutes',
                '15'              => '15 minutes',
                '20'              => '20 minutes'
            ));
            ?>
        </div>
    </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
