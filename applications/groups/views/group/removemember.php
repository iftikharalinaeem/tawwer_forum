<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->title(); ?></h1>

<div class="StructuredForm Form-Confirm">
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
<div class="P">
    <?php echo t('You can remove or ban this member from the group.', 'You can remove or ban this member from the group. Banned members won\'t be able to join the group again.'); ?>
</div>
<div class="P">
    <?php
        echo $this->Form->radioList('Type', ['Removed' => 'Remove', 'Banned' => 'Ban']);
    ?>
</div>
<div class="Buttons Buttons-Confirm">
    <?php
    echo $this->Form->button('OK', ['class' => 'Button Primary']);
    echo ' '.$this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
    ?>
</div>
<?php
echo $this->Form->close();
?>
</div>
