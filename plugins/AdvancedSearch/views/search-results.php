<?php if (!defined('APPLICATION')) exit(); ?>

<ol class="DataList DataList-Search" start="<?php echo $this->Data('From'); ?>">
   <?php foreach ($this->Data('SearchResults') as $Row): ?>
   <li class="Item Item-Search">
      <h3><?php echo Anchor($Row['Title'], $Row['Url']); ?></h3>
      <div class="Item-Body">
         <div class="Excerpt">
            <?php echo $Row['Summary']; ?>
         </div>
         <div class="Meta">
         <?php
            echo ' <span class="MItem-Author">'.
               UserAnchor($Row).
               '</span>';
            
            echo Bullet(' ');
            echo ' <span clsss="MItem-DateInserted">'.
               Anchor(Gdn_Format::ToDate($Row['DateInserted']), $Row['Url']).
               '</span> '; 
         ?>
            
         <?php
         echo Bullet(' ');
         if (isset($Row['Breadcrumbs'])) {
            echo ' <span class="MItem-Location">'.Gdn_Theme::Breadcrumbs($Row['Breadcrumbs'], FALSE).'</span> ';
         }
         ?>
         </div>
      </div>
   </li>
   <?php endforeach; ?>
</ol>