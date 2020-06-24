<?php if (!defined('APPLICATION')) return; ?>
<h1 class="H HomepageTitle"><?php echo $this->data('Title'); ?></h1>
<div class="P PageDescription"><?php echo $this->description(); ?></div>
<div class="CategoriesWrap">
<?php
$Categories = $this->data('CategoryTree');

//decho($Categories);
//die();

if (c('Vanilla.Categories.DoHeadings')) {
   foreach ($Categories as $Category) {
      ?>
      <div id="CategoryGroup-<?php echo $Category['UrlCode']; ?>" class="CategoryGroup CategoryGroup-<?php echo $Category['CssClass']; ?> CategoryGroup-<?php echo Gdn_Format::alphaNumeric($Category['UrlCode']); ?>">
         <h2 id="Category_<?php echo $Category['CategoryID']; ?>" class="H Depth1 Depth-1 <?php echo $Category['CssClass']; ?> Category-<?php echo Gdn_Format::alphaNumeric($Category['UrlCode']); ?>">
            <?php echo htmlspecialchars($Category['Name']); ?>
         </h2>
         <?php
         writeCategoryList($Category['Children'], 2);
         ?>
      </div>
      <?php
   }
} else {
   writeCategoryList($Categories, 1);
}
?>
</div>