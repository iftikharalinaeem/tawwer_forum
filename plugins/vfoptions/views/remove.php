<?php if (!defined('APPLICATION')) exit();
$About = ArrayValue(0, $this->RequestArgs, '');
$Domain = str_replace(array('http://', '/'), array('', ''), Gdn::Config('Garden.Domain', 'your_forum_name.vanillaforums.com'));
?>
<h1>Remove Upgrade</h1>
<div class="Legal">
   <p>You will be charged for the remainder of your current billing cycle. Are you sure you want to remove this upgrade?</p>
</div>
<?php
echo $this->Form->Errors();
echo $this->Form->Open();
echo $this->Form->Close('Remove My Upgrade ↯');