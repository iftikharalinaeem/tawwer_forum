<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
$Cf = $this->ConfigurationModule;

$Cf->Render();