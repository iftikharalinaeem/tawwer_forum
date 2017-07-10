<?php if (!defined('APPLICATION')) return; ?>
<h1><?php echo $this->Data('Title'); ?></h1>

<div class="Wrap FormWrapper">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

if ($this->Data('Warning.Expired')) {
   echo Wrap(T('This warning has expired. Do you want to completely delete it?'), 'div', ['class' => 'Warning']);
   echo $this->Form->Hidden('RemoveType', 'delete');
} else {
   echo Wrap('<b>'.T('Do you want to expire or delete this warning?').'</b>', 'div', ['class' => 'P']);

   echo '<div class="P">'.
      $this->Form->Radio('RemoveType', T('Just expire the warning.'), ['value' => 'expire']).
      '</div>';

   echo '<div class="P">'.
      $this->Form->Radio('RemoveType', T('Completely delete the warning.'), ['value' => 'delete']).
      '</div>';
}

echo '<div class="Buttons Buttons-Confirm">',
   $this->Form->Button(T('OK')), ' ',
   $this->Form->Button(T('Cancel'), ['type' => 'button', 'class' => 'Button Close']),
   '</div>';
echo $this->Form->Close();
?>
</div>
