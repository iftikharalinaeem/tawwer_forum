<?php if (!defined('APPLICATION')) exit();

// PA theme has their user view in the userphoto module view in the panel
?>
<div class="ProfileBreadCrumb">
   <span class="BreadCrumb FirstCrumb"> &rarr; </span><?php echo FormatPossessive($this->User->Name).' Profile'; ?>
</div>   
