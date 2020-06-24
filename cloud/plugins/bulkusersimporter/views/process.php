<?php if (!defined('APPLICATION')) exit(); ?>

<?php

if (Gdn::controller()->deliveryType() == DELIVERY_TYPE_ALL) {
   echo '<h1>'.$this->data('Title').'</h1>';
   echo '<div class="Info">'.$this->data('status').'</div>';
   echo $this->Form->open(),
      $this->Form->errors(),
      $this->Form->close();
} else {
   echo $this->data('status');
}
