<?php if (!defined('APPLICATION')) exit();

echo '<h1>'.$this->data('Title').'</h1>';

echo '<div class="Info">'.
   t('Continuing this discussion in private puts all new comments into a private conversation.',
   'Continuing this discussion in private puts all new comments into a private conversation. 
    <b>Only</b> users that you select below will be able to view and add new comments. This does not affect the comments already in this discussion.').
   '</div>';

echo $this->Form->open();
echo $this->Form->errors();

echo '<div class="P Info2">'.
   t('Select the people you want in the conversation.').
   '</div>';

echo '<ul class="CheckBoxList">';

$CheckedIDs = $this->Form->getValue('UserID', []);
if (!is_array($CheckedIDs))
   $CheckedIDs = [];

foreach ($this->data('Users') as $User) {
   $Label = htmlspecialchars($User['Name']);
   
   $CssClass = '';
   if ($User['UserID'] == $this->data('Discussion.InsertUserID')) {
      $CssClass = 'Discussion-Starter';
      
      $Type = $this->data('Discussion.Type');
      if (!$Type)
         $Type = 'Discussion';
      
      $Label .= ' ('.t("started the $Type", 'Started the discussion').')';
   }
   
   $Attributes = ['Value' => $User['UserID']];
   $Checked = in_array($User['UserID'], $CheckedIDs);
   if ($Checked)
      $Attributes['checked'] = 'checked';
   
   echo '<li class="'.$CssClass.'"><div class="P">'.
      $this->Form->checkBox('UserID[]', $Label, $Attributes);
   echo '</div></li>';
}

echo '</ul>';

echo '<div class="Buttons">'.
   $this->Form->button(t('Continue')).
   '</div>';

echo $this->Form->close();