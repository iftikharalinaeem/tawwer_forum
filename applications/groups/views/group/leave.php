<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<div class="P">
    <?php echo t('Are you sure you want to leave this group?'); ?>
</div>

<div class="Buttons Buttons-Confirm">
    <?php
    echo $this->Form->button('OK', ['class' => 'Button Primary']);
    echo ' '.$this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
    ?>
</div>
<?php
echo $this->Form->close();