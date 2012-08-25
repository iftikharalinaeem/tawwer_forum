<?php if (!defined('APPLICATION')) exit(); 
include_once 'reaction_functions.php';
echo Wrap($this->Data('Title'), 'h1 class="H"'); 
?>
<div class="BestOfData">
   <ul class="DataList BestOfList">
      <?php include_once('datalist.php'); ?>
   </ul>
   <?php echo PagerModule::Write(); ?>
</div>