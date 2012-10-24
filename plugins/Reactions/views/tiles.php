<?php if (!defined('APPLICATION')) exit();
// Set the video embed size for this page explicitly (in memory only).
SaveToConfig('Garden.Format.EmbedSize', '594x335', array('Save' => FALSE));

if (!function_exists('WriteReactions'))
   include $this->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');

echo Wrap($this->Data('Title'), 'h1 class="H"');
echo '<div class="BestOfWrap">';
   echo Gdn_Theme::Module('BestOfFilterModule');
   echo '<div class="Tiles ImagesWrap">';
   foreach ($this->Data('Data', array()) as $Record) {
      WriteImageItem($Record, 'Tile ImageWrap Invisible');
   }
   
   echo '</div>';
   echo PagerModule::Write(array('MoreCode' => 'Load More')); 
   echo '<div class="LoadingMore"></div>';
echo '</div>';