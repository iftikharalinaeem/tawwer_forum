<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
/*$Editing = isset($this->Comment);
if ($Editing)
   $this->Form->SetFormValue('Body', $this->Comment->Body);
*/
?>
<div class="MessageForm CommentForm Hidden">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
echo $this->Form->Button('Comment', array('class' => 'Button CommentButton'));
$this->FireEvent('AfterFormButtons');
echo $this->Form->Close();
?>
</div>