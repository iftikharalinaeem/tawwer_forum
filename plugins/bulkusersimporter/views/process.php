<?php if (!defined('APPLICATION')) exit(); ?>

<?php

if (Gdn::Controller()->DeliveryType() == DELIVERY_TYPE_ALL) {
   echo '<h1>'.$this->Data('Title').'</h1>';
   echo '<div class="Info">'.$this->Data('status').'</div>';
   echo $this->Form->Open(),
      $this->Form->Errors(),
      $this->Form->Close();
} else {
   echo $this->Data('status');
}
