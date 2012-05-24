<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<style class="text/css">
   .OnlineSettings ul {
      padding-bottom: 15px !important;
   }
</style>
<h1><?php echo T("Online Settings"); ?></h1>
<div class="InfoRow OnlineSettings">
   
   <div class="Warning">Placement and visual settings.</div>
   <ul>
      
      <li><?php
         echo $this->Form->Label("Where should the Online list be displayed?", "Plugins.Online.Location");
         echo $this->Form->DropDown('Plugins.Online.Location', array(
            'every'           => "On every page",
            'discussionlists' => "Only on Discussion and Category lists",
            'discussions'     => "On all discussion pages",
            'custom'          => "I'll place it manually with my theme"
         ));
      ?></li>
      
      <li><?php
         echo $this->Form->Label("How should the list be rendered?", "Plugins.Online.Style");
         echo $this->Form->DropDown('Plugins.Online.Style', array(
            'pictures'        => "User Icons",
            'links'           => "User Links"
         ));
      ?></li>
      
      <li><?php
         echo $this->Form->Label("Hide the Online list from guests?", "Plugins.Online.HideForGuests");
         echo $this->Form->DropDown('Plugins.Online.HideForGuests', array(
            'true'            => "Yes, only show to logged-in members",
            'false'           => "No, anyone may view the list"
         ));
      ?></li>
   </ul>
   
   <div class="Warning">Internal settings.</div>
   <ul>
      <li><?php
         echo $this->Form->Label("How long are you 'online' for after you visit a page?", "Plugins.Online.PruneDelay");
         echo $this->Form->DropDown('Plugins.Online.PruneDelay', array(
            '5'               => '5 minutes',
            '10'              => '10 minutes',
            '15'              => '15 minutes',
            '20'              => '20 minutes'
         ));
      ?></li>
   </ul>

</div>

<?php echo $this->Form->Close('Save');
