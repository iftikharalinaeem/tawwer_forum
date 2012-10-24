<?php if (!defined('APPLICATION')) exit();
if (!function_exists('WriteReactions'))
   include $this->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');

foreach ($this->Data('Data', array()) as $Record) {
   WriteImageItem($Record, 'Tile ImageWrap Invisible');
}