<?php if (!defined('APPLICATION')) return; ?>
<h1 class="H HomepageTitle"><?php echo $this->Data('Title'); ?></h1>
<div class="P PageDescription"><?php echo $this->Description(); ?></div>
<div class="CategoriesWrap">
<?php
$Categories = CategoryModel::MakeTree($this->Data('Categories'));

//decho($Categories);
//die();

if (C('Vanilla.Categories.DoHeadings')) {
   foreach ($Categories as $Category) {
      ?>
      <div id="CategoryGroup-<?php echo $Category['UrlCode']; ?>" class="CategoryGroup CategoryGroup-<?php echo Gdn_Format::AlphaNumeric($Category['UrlCode']); ?>">
         <h2 id="Category_<?php echo $Category['CategoryID']; ?>" class="H Depth1 Depth-1 Category-<?php echo Gdn_Format::AlphaNumeric($Category['UrlCode']); ?>">
            <?php echo Anchor($Category['Name'], $Category['Url']); ?>
         </h2>
         <?php
         WriteCategoryList($Category['Children'], 2);
         ?>
      </div>
      <?php
   }
} else {
   WriteCategoryList($Categories, 1);
}
?>
</div>