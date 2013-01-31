<?php

if (!function_exists('WriteGroupBanner')):
   
function WriteGroupBanner($Group) {
   if (!$Group['Banner'])
      return;
   
   echo Img(Gdn_Upload::Url($Group['Banner']));
}
endif;

if (!function_exists('WriteGroupCards')):
   
/**
 * Write a list of groups out as cards.
 * @param array $Groups
 */
function WriteGroupCards($Groups) {
   decho($Groups, 'Group Cards');
}
endif;

if (!function_exists('WriteGroupIcon')):

function WriteGroupIcon($Group) {
   if (!$Group['Icon'])
      return;
   
   echo Img(Gdn_Upload::Url($Group['Icon']));
}
   
endif;


if (!function_exists('WriteGroupList')):

/**
 * Write a list of groups out as a list.
 * @param array $Groups
 */
function WriteGroupList($Groups) {
   echo '<ul class="DataList DataList-Groups">';
   
   foreach ($Groups as $Group) {
      echo '<li class="Item Item-Group">';
      
      echo Anchor(htmlspecialchars($Group['Name']), GroupUrl($Group));
      
      echo '</li>';
   }
   
   echo '</ul>';
}

endif;