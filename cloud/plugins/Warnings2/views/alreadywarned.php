<?php if (!defined('APPLICATION')) exit(); ?>

  <h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();

echo '<div class="P">'.sprintf(t('The user has already been warned for the %s:'), t('post')).'</div>';
?>
<ul>
    <?php foreach ($this->Data['WarnedPostUrls'] as $WarnedPostURL) {
        echo '<li>'.anchor($WarnedPostURL, url($WarnedPostURL, true)).'</li>';
    }
    ?>
</ul>
<?php
echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->button('OK', ['class' => 'Button Primary']);
echo '<div>';
echo $this->Form->close();
?>