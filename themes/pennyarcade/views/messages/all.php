<?php if (!defined('APPLICATION')) exit();
?>
<div class="Tabs ConversationsTabs">
   <h1><?php echo T('All Conversations'); ?></h1>
   <div class="SubTab">
      <span class="BreadCrumb FirstCrumb"> &rarr; </span><?php
      echo Anchor(T('All Conversations'), '/messages/all');
      ?>
   </div>   
</div>
<?php
if ($this->ConversationData->NumRows() > 0) {
?>
<ul class="Condensed DataList Conversations">
   <?php
   $ViewLocation = $this->FetchViewLocation('conversations');
   include($ViewLocation);
   ?>
</ul>
<?php
echo $this->Pager->ToString();
} else {
   echo '<div class="Empty">'.T('You do not have any conversations.').'</div>';
}
