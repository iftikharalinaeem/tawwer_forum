<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo sprintf('Infraction Management: %s', $this->Data['Username']); ?></h2>
<div class="InfractionPopup">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
// echo Wrap(sprintf('Infraction History for %s', $this->Data['Username']), 'h3');
include($this->FetchViewLocation('plugins/Infractions/views/summary.php'));
include($this->FetchViewLocation('plugins/Infractions/views/infractionform.php'));
echo $this->Form->Close();
?>
</div>