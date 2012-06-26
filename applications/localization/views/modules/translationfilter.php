<?php if (!defined('APPLICATION')) return ?>
<div class="Box">
   <h4><?php echo T('Filter'); ?></h4>
   <?php
   echo $this->Form->Open(array('action' => '/localization/savefilter?target='.urlencode(Gdn::Controller()->CanonicalUrl())));
   
   echo $this->Form->CheckBox('Core', 'Core');
   echo $this->Form->CheckBox('Admin', 'Admin');
   echo $this->Form->CheckBox('Addon', 'Addons');
   
   echo $this->Form->Close('Update');
   ?>
</div>