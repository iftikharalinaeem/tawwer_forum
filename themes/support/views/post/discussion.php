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
      echo '<div class="Title Question">Ask your question.</div>';
      echo '<div class="Title Problem">Describe your problem.</div>';
      echo '<div class="Title Idea">Share your idea.</div>';
      echo '<div class="Title Kudos">Hand out kudos.</div>';
      echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
      echo $this->Form->Label('Give your discussion a good title', 'Name');
      echo '<div class="SubTitle">The more descriptive you make your title, the better the chances that your discussion will be noticed and get responses.</div>';
      echo '<div class="Examples Question">';
         echo '<div class="Example Good">Good: How do I delete a user?</div>';
         echo '<div class="Example Bad">Bad: ???</div>';
      echo '</div>';
      echo '<div class="Examples Problem">';
         echo '<div class="Example Good">Good: Error during install process: "no input file specified"</div>';
         echo '<div class="Example Bad">Bad: arrrrrrrrrrrrrggghhhhhhh!!!!!!</div>';
      echo '</div>';
      echo '<div class="Examples Idea">';
         echo '<div class="Example Good">Good: Vanilla should have a poll plugin</div>';
         echo '<div class="Example Bad">Bad: i have an idea</div>';
      echo '</div>';

      echo $this->Form->TextBox('Name', array('maxlength' => 100));
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
