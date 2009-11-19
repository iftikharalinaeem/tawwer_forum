<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('Delete a Forum'); ?></h1>
<ul>
   <li>
      <div class="Warning"><?php echo Gdn::Translate("Warning: All of your data will be lost. Once you delete, there is absolutely no recovery of any kind."); ?></div>
   </li>
   <li>
      <div class="Info"><strong>Forum Name:</strong> <?php echo $this->Site->Name; ?>.vanillaforums.com</div>
   </li>
</ul>
<?php echo $this->Form->Close('Delete Forum');