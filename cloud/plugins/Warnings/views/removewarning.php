<?php if (!defined('APPLICATION')) return; ?>
<h1><?php echo $this->data('Title'); ?></h1>

<div class="Wrap FormWrapper">
<?php
echo $this->Form->open();
echo $this->Form->errors();

if ($this->data('Warning.Expired')) {
   echo wrap(t('This warning has expired. Do you want to completely delete it?'), 'div', ['class' => 'Warning']);
   echo $this->Form->hidden('RemoveType', 'delete');
} else {
   echo wrap('<b>'.t('Do you want to expire or delete this warning?').'</b>', 'div', ['class' => 'P']);

   echo '<div class="P">'.
      $this->Form->radio('RemoveType', t('Just expire the warning.'), ['value' => 'expire']).
      '</div>';

   echo '<div class="P">'.
      $this->Form->radio('RemoveType', t('Completely delete the warning.'), ['value' => 'delete']).
      '</div>';
}

echo '<div class="Buttons Buttons-Confirm">',
   $this->Form->button(t('OK')), ' ',
   $this->Form->button(t('Cancel'), ['type' => 'button', 'class' => 'Button Close']),
   '</div>';
echo $this->Form->close();
?>
</div>
