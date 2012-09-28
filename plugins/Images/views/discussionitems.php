<?php if (!defined('APPLICATION')) exit();
// Need this for comment options list, etc.
if (!function_exists('WriteCommentForm'))
   include $this->FetchViewLocation('helper_functions', 'discussion', 'vanilla');

if (!function_exists('WriteImageItem'))
   include $this->FetchViewLocation('helper_functions', '', 'plugins/Images');

// Write the comments
$Comments = $this->Data('Comments')->ResultArray();
foreach ($Comments as $Comment) {
   WriteImageItem($Comment);
}