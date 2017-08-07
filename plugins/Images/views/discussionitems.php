<?php if (!defined('APPLICATION')) exit();
// Need this for comment options list, etc.
if (!function_exists('WriteCommentForm'))
   include $this->fetchViewLocation('helper_functions', 'discussion', 'vanilla');

if (!function_exists('WriteImageItem'))
   include $this->fetchViewLocation('helper_functions', '', 'plugins/Images');

// Write the comments
$Comments = $this->data('Comments')->resultArray();
foreach ($Comments as $Comment) {
   writeImageItem($Comment);
}