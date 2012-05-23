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
<h1><?php echo $this->Data('Title'); ?></h1>

<div class="PageInfo">
   <p><b>Warning! If your forum has a lot of posts then this process will take a very long time.</b></p>
   <p>This page will go through all of your site's posts and check them for spam.</p>
</div>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
echo $this->Form->InputPrefix;
?>

<div class="Wrap">
   <div class="P">
      <?php
      echo $this->Form->CheckBox('VerifyModerators', 'Mark moderators verified before cleaning. (recommended)', array('Default' => TRUE));
      ?>
   </div>

   
<div class="TypeList">
   <h2>Select the Types of Posts to Clean</h2>
<div class="Info2">
   Select the types of posts you want to check for spam and click <b>Start</b>.
</div>   
<?php foreach ($this->Data('Types') as $Type => $Info): ?>
   <div class="P">
      <?php
      echo $this->Form->CheckBox('Type_'.$Type, $Info['Label'], array('value' => $Type));
      if ($this->Data('StartCleanSpam') && $this->Form->GetFormValue('Type_'.$Type)) {
         echo ' <span class="Count"><span class="CountSpam">0</span> / <span class="CountAll">0</span> Found</span>';
      }
      ?>
   </div>
<?php endforeach ?>
</div>
   
</div>

<?php
echo '<div class="Buttons">';
echo $this->Form->Button('Start');
echo '</div>';
echo $this->Form->Close();