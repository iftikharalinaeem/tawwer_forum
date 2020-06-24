<?php if (!defined('APPLICATION')) exit; ?>
<style>
   .TypeList label {
      display: inline-block;
      min-width: 160px;
   }
   
   .Count {
      background: #02639E;
      border-radius: 2px;
      color: #fff;
      padding: 2px 4px;
   }
   
   .P {
      margin: 1em 0;
   }
</style>
<h1><?php echo $this->data('Title'); ?></h1>

<div class="PageInfo">
   <p><b>Warning! If your forum has a lot of posts then this process will take a very long time.</b></p>
   <p>This page will go through all of your site's posts and check them for spam.</p>
</div>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>

<div class="Wrap">
   <div class="P">
      <?php
      echo $this->Form->checkBox('VerifyModerators', 'Mark moderators verified before cleaning. (recommended)', ['Default' => TRUE]);
      ?>
   </div>

   
<div class="TypeList">
   <h2>Select the Types of Posts to Clean</h2>
<div class="Info2">
   Select the types of posts you want to check for spam and click <b>Start</b>.
</div>   
<?php foreach ($this->data('Types') as $Type => $Info): ?>
   <div class="P">
      <?php
      echo $this->Form->checkBox('Type_'.$Type, $Info['Label'], ['value' => $Type]);
      if ($this->data('StartCleanSpam') && $this->Form->getFormValue('Type_'.$Type)) {
         echo ' <span class="Count"><span class="CountSpam">0</span> / <span class="CountAll">0</span> Found</span>';
      }
      ?>
   </div>
<?php endforeach ?>
</div>
   
</div>

<?php
echo '<div class="Buttons">';
echo $this->Form->button('Start');
echo '</div>';
echo $this->Form->close();