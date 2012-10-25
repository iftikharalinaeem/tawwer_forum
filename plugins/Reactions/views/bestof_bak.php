<?php if (!defined('APPLICATION')) exit(); ?>
<?php echo Wrap($this->Data('Title'), 'h1 class="H"'); ?>
<div class="BestOfData">
   <?php echo Gdn_Theme::Module('BestOfFilterModule'); ?>
   <div class="DataList BestOfList">
      <?php include_once('bestoflist.php'); ?>
   </div>
   <?php echo PagerModule::Write(array('MoreCode' => 'Load More')); ?>
   <div class="LoadingMore"></div>
</div>