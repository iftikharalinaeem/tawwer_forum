<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<ul class="MessageList Discussion">
   <?php echo $this->FetchView('comments'); ?>
</ul>
<?php
if ($Session->IsValid()) 
   echo $this->FetchView('comment', 'post');