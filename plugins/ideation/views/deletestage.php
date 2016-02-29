<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <div class="Info Center">
        <?php
        printf(t('Are you sure you want to delete this %s?'), t('Stage'));
        ?>
    </div>

    <div class="Buttons Buttons-Confirm">
        <?php
        echo $this->Form->button('Yes');
        echo $this->Form->button('No', array('type' => 'button', 'class' => 'Button Close'));
        ?>
    </div>
<?php
echo $this->Form->close();
