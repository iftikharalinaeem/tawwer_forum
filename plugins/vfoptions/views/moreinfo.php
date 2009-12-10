<?php if (!defined('APPLICATION')) exit();
$About = ArrayValue(0, $this->RequestArgs, '');
?>
<h1><?php
if ($About == 'addadomain')
   echo 'Add a Domain';
else if ($About == 'adremoval')
   echo 'Ad Removal';
else if ($About == 'singlesignon')
   echo 'Single Sign-on';
else if ($About == 'customcss')
   echo 'Custom CSS';
else if ($About == 'fileuploading')
   echo 'File Uploading';
else if ($About == 'datatransfer')
   echo 'Data Transfer';
else 
   echo 'More Info?';
?></h1>
<div class="Info"><?php
if ($About == 'addadomain') {
   echo 'Do Stuff!';
} else if ($About == 'adremoval') {
   echo 'Do Stuff!';
} else if ($About == 'singlesignon') {
   echo 'Do Stuff!';
} else if ($About == 'customcss') {
   echo 'Do Stuff!';
} else if ($About == 'fileuploading') {
   echo 'Do Stuff!';
} else if ($About == 'datatransfer') {
   echo 'Do Stuff!';
} else {
   echo "You are looking for more information, but we don't seem to have what you're looking for. Contact us for help! support [at] vanillaforums [dot] com";
}
?></div>
