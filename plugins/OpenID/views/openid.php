<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T($this->Data['Description']); ?>
</div>
<div class="FilterMenu">
      <?php
      $PluginName = $this->Plugin->GetPluginKey('Name');
      echo Anchor(
         T($this->Plugin->IsEnabled() ? "Disable {$PluginName}" : "Enable {$PluginName}"),
         $this->Plugin->AutoTogglePath(),
         'SmallButton'
      );
   ?>
</div>