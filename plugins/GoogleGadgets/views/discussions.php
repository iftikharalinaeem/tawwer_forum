<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$Alt = '';
if (!function_exists('WriteDiscussion'))
   include(PATH_PLUGINS . DS . 'GoogleGadgets' . DS . 'views' . DS . 'helper_functions.php');
   
foreach ($this->DiscussionData->Result() as $Discussion) {
   $Alt = $Alt == ' Alt' ? '' : ' Alt';
   WriteDiscussion($Discussion, $this, $Session, $Alt);
}