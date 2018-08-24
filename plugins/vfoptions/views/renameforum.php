<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Rename Forum'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <div class="Warning"><?php echo T("Warning: We do not set up any redirects from your old forum name. If this is a popular forum, you're probably going to upset a lot of people."); ?></div>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Forum Name', 'Name');
         echo $this->Form->TextBox('Name', array('style' => 'text-align: right;', 'maxlength' => 50));
         echo '<span style="font-size: 22px; font-weight: bold; color: #02455B;">.vanillaforums.com</span>';
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Change Name');