<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
class DowntimePlugin implements Gdn_IPlugin {
   
   public function Base_Render_Before(&$Sender) {
      /*
      $Sender->AddAsset('Content', '<div class="Warning" style="margin-bottom: 10px;">
   <div style="color: #444; font-size: 110%; font-weight: bold;">Scheduled Maintenance</div>
   This forum will be going offline for scheduled maintenance between 7pm and 8pm MDT.
</div>', 'Notices');
      */
   }
   
   public function Setup() {
      // No setup required.
   }
}