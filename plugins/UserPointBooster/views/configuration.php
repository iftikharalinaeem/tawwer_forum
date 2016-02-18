<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li><?php
            echo $this->Form->label(t('Point(s) awarded per post'), 'UserPointBooster.PostPointValue');
            echo $this->Form->input('UserPointBooster.PostPointValue');
            ?></li>
    </ul>
<?php echo $this->Form->close('Save');
