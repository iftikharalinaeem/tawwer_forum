<?php if (!defined('APPLICATION')) exit();
if (!function_exists('WriteReactions'))
   include $this->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');

if (!function_exists('WriteImageItem'))
   include $this->FetchViewLocation('helper_functions', '', 'plugins/Images');

foreach ($this->Data('Data', array()) as $Record) {
   WriteImageItem($Record, 'ImageWrap Invisible');
}