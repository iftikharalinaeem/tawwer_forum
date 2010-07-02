<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$CancelUrl = '/vanilla/discussions';
if ($this->CategoryID > 0 && $this->CategoryData->NumRows() > 0) {
   foreach ($this->CategoryData->Result() as $Cat) {
      if ($Cat->CategoryID == $this->CategoryID) {
         $CancelUrl = '/vanilla/discussions/'.$Cat->Code;
         break;
      }      
   }
}
?>
<div id="DiscussionForm">
   <h2><?php echo property_exists($this, 'Discussion') ? 'Edit Discussion' : 'Start a New Discussion'; ?></h2>
   <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
      echo $this->Form->Label('Give your discussion a descriptive title', 'Name');
      echo $this->Form->TextBox('Name', array('maxlength' => 100));
      echo '<div class="Examples">';
         echo '<div class="Example Good">Good: Error during install process: "no input file specified"</div>';
         echo '<div class="Example Bad">Bad: arrrrrrrrrrrrrggghhhhhhh!!!!!!</div>';
      echo '</div>';

      echo $this->Form->Label('Ask your question', 'Body');
      echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
      echo '<div class="Help">Provide as much information as possible.</div>';
      echo $this->Form->Button((property_exists($this, 'Discussion')) ? 'Save' : 'Post Discussion');
      if (!property_exists($this, 'Discussion') || !is_object($this->Discussion) || (property_exists($this, 'Draft') && is_object($this->Draft))) {
         echo $this->Form->Button('Save Draft');
      }
      echo $this->Form->Button('Preview');
      $this->FireEvent('AfterFormButtons');
      echo Anchor(Gdn::Translate('Cancel'), $CancelUrl, 'Cancel');
      echo $this->Form->Close();
   ?>
</div>
