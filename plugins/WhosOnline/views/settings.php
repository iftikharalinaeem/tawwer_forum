<?php if (!defined('APPLICATION')) {
    exit();
} ?>
    <h2><?php echo t('Privacy Settings'); ?></h2>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Settings');
            echo $this->Form->checkBox('Plugin.WhosOnline.Invisible', 'Hide my online status from other members');
            ?>
        </li>

    </ul>
<?php echo $this->Form->close('Save');
