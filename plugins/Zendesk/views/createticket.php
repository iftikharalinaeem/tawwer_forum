<?php if (!defined('APPLICATION')) exit(); ?>
   <h2><?php echo T('Zendesk'); ?></h2>
<?php
$ZendeskEmail = $this->Data['Plugin.Zendesk.Data']['InsertEmail'];
$ZendeskName = $this->Data['Plugin.Zendesk.Data']['InsertName'];

echo $this->Form->Open();
echo $this->Form->Errors();

echo $this->Form->Hidden('Plugin.Zendesk.URL');
echo $this->Form->Hidden('Plugin.Zendesk.UserId');
echo $this->Form->Hidden('Plugin.Zendesk.UserName');
echo $this->Form->Hidden('Plugin.Zendesk.InsertName');
echo $this->Form->Hidden('Plugin.Zendesk.InsertEmail');
?>
   <p>Complete this form to submit a Ticket to your <a target="_blank" href="<?php echo C('Plugins.Zendesk.Url') ?>">Zen
         Desk</a></p>
   <p>The user who submitted the Post will get an Email from your Zendesk telling them how to proceed.</p>

   <ul>
      <li>
         <div class="Warning">
            <?php echo T('SomeMessage', "Submitting this form will submit this content to Zendesk on behalf of the user"); ?>
         </div>
      </li>
      <li>Name: <?php echo htmlspecialchars($ZendeskName); ?></li>
      <li>Email: <?php echo htmlspecialchars($ZendeskEmail); ?></li>
      <?php
      echo $this->Form->Label('Subject Title', 'Plugin.Zendesk.Title');
      echo $this->Form->TextBox('Plugin.Zendesk.Title');
      ?>
      <li>
         <?php
         echo $this->Form->Label('Body', 'Plugin.Zendesk.Body');
         echo $this->Form->TextBox('Plugin.Zendesk.Body', array('MultiLine' => TRUE));
         ?>
      </li>
   </ul>

   <div style="width: 400px"></div>
<?php echo $this->Form->Close('Send to Zendesk!');

