<?php if (!defined('APPLICATION')) exit();
$this->Title(T('Ask a Question'));
$Session = Gdn::Session();
$CancelUrl = '/vanilla/discussions';
if (C('Vanilla.Categories.Use') && $this->CategoryID > 0 && $this->CategoryData->NumRows() > 0) {
   foreach ($this->CategoryData->Result() as $Cat) {
      if ($Cat->CategoryID == $this->CategoryID) {
         $CancelUrl = '/vanilla/discussions/'.$Cat->Code;
         break;
      }      
   }
}
?>
<div id="DiscussionForm">
   <h2><?php echo property_exists($this, 'Discussion') ? 'Edit Question' : 'Ask a Question'; ?></h2>
   <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
   ?>
   <div class="PostHelp">
      <div class="Help HelpTitle">
         <h4>How to Ask</h4>
         <ul>
            <li>We prefer questions that can be <em>answered</em>, not just discussed.</li>
            <li>Provide details.</li>
            <li>Write clearly and descriptively.</li>
         </ul>
         <div class="Examples">
            <div class="Example Bad"><strong>Bad:</strong> HELP!!!!!!</div>
            <div class="Example Good"><strong>Good:</strong> Error during install process: "no input file specified"</div>
         </div>
      </div>
      <div class="Help HelpFormat">
         <h4>How to Format</h4>
         <ul>
            <li>HTML is allowed</li>
            <li>Place code samples in &lt;code&gt; tags</li>
            <li>Provide details like:
               <ul>
                  <li>Exact error messages</li>
                  <li>Browser & version</li>
                  <li>PHP version</li>
                  <li>MySQL version</li>
               </ul>
            </li>
         </ul>
      </div>
      <div class="Help HelpTags">
         <h4>How to Tag</h4>
         <p>A tag is a keyword that categorizes your question with similar questions.</p>
         <ul>
            <li>When possible, use existing tags</li>
            <li>Favour popular tags</li>
            <li>Use common abbreviations</li>
            <li>Don't include synonyms</li>
            <li>Combine multiple words into a single word with dashes</li>
            <li>Max 5 tags</li>
            <li>Max 25 characters per tag</li>
            <li>Tag characters: a-z 0-9 + # _ .</li>
            <li>Separate tags with a space</li>
         </ul>
      </div>
   </div>
   <?php
      echo $this->Form->Label('Question Title', 'Name');
      echo $this->Form->TextBox('Name', array('maxlength' => 100));

      echo $this->Form->Label('Ask your question', 'Body');
      echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
      $this->FireEvent('BeforeFormButtons');
      echo $this->Form->Button((property_exists($this, 'Discussion')) ? 'Save' : 'Ask Question');
      if (!property_exists($this, 'Discussion') || !is_object($this->Discussion) || (property_exists($this, 'Draft') && is_object($this->Draft))) {
         echo $this->Form->Button('Save Draft');
      }
      echo $this->Form->Button('Preview');
      $this->FireEvent('AfterFormButtons');
      echo Anchor(Gdn::Translate('Cancel'), $CancelUrl, 'Cancel');
      echo $this->Form->Close();
   ?>
</div>
