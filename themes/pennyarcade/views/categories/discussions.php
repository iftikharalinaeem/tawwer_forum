<?php if (!defined('APPLICATION')) exit();
$ViewLocation = $this->FetchViewLocation('discussions', 'discussions');
?>
<div class="ProfileBreadCrumb">
   <span class="BreadCrumb FirstCrumb"> &rarr; </span><?php echo 'Recent threads by forum'; ?>
</div>   
<div class="Categories">
   <?php foreach ($this->CategoryData->Result() as $Category) {
      if ($Category->CategoryID <= 0)
         continue;

      $this->Category = $Category;
      $this->DiscussionData = $this->CategoryDiscussionData[$Category->CategoryID];
      if ($this->DiscussionData->NumRows() > 0) {
   ?>
   <div class="CategoryBox Category-<?php echo $Category->UrlCode; ?>">
      <div class="Tabs CategoryTabs">
         <h2><?php echo $Category->Name; ?></h2>
      </div>
      <table class="DiscussionHeading">
         <tr>
            <td class="DiscussionName">Thread</td>
            <td class="User FirstUser"><div class="Wrap">Original Post</div></td>
            <td class="User LastUser"><div class="Wrap">Most Recent Post</div></td>
            <td class="Count CountComments"><div class="Wrap">Replies</div></td>
            <td class="Count CountViews">Views</td>
         </tr>
      </table>
      <ul class="DataList Discussions">
         <?php include($this->FetchViewLocation('discussions', 'discussions')); ?>
      </ul>
      <div class="Foot"><?php echo Anchor('More threads in '.$Category->Name, '/categories/'.$Category->UrlCode, 'TabLink'); ?></div>
   </div><?php
      }
   }
   ?>
</div>