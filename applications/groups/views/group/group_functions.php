<?php if (!defined('APPLICATION')) exit(); 

if (!function_exists('DateTile')):
/**
 * Get HTML to display date as a calendar tile block.
 * 
 * @param string $Date
 */
function DateTile($Date) {
   return '
   <span class="DateTile">
      <span class="Month">Dec</span>
      <span class="Day">29</span>
   </span>';
}
endif;


if (!function_exists('WriteDiscussionBlog')):
/**
 * Output discussions in expanded format with excerpts.
 * 
 * @param array $Discussions 
 * @param string $EmptyMessage What to show when there's no content.
 */
function WriteDiscussionBlog($Discussions, $EmptyMessage = '') {
   if (!$Discussions)
      WriteEmptyState($EmptyMessage);
   
   
}
endif;


if (!function_exists('WriteDiscussionList')):
/**
 * Output discussions in a compact list.
 * 
 * @param array $Discussions 
 * @param string $EmptyMessage What to show when there's no content.
 */
function WriteDiscussionList($Discussions, $EmptyMessage = '') {
   if (!$Discussions)
      WriteEmptyState($EmptyMessage);
   
   //if (C('Vanilla.Discussions.Layout') == 'table')
   //WriteDiscussionRow($Discussion, $this, $Session, $Alt);
   if (is_array($Discussions)) {
      include_once(PATH_APPLICATIONS .'/vanilla/views/discussions/helper_functions.php');
      include_once(PATH_APPLICATIONS .'/vanilla/views/modules/helper_functions.php');   
      echo '<ul class="DataList Discussions">';
      foreach ($Discussions as $Discussion) {
         WriteModuleDiscussion((object)$Discussion, 'Group');
      }
      echo '</ul>';
   }
}
endif;


if (!function_exists('WriteEmptyState')):
/**
 * Output a generically formatted "empty state" message box.
 * 
 * @param string $Message HTML.
 */
function WriteEmptyState($Message) {
   if ($Message)
      echo Wrap($Message, 'p', array('class' => 'EmptyMessage'));
}
endif;


if (!function_exists('WriteEventList')) :
/**
 * Output an HTML list of events or an empty state message.
 * 
 * @param array $Events 
 * @param string $EmptyMessage What to show when there's no content.
 */   
function WriteEventList($Events, $EmptyMessage = '') {
   if (!$Events)
      WriteEmptyState($EmptyMessage);
   
   if (is_array($Events)) {
      echo '<ul class="DataList DataList-Events">';
      foreach ($Events as $Event) {
         echo 
         '<li class="Event">
            '.DateTile($Event['DateScheduled']).'
            <h3 class="Event-Title">'.Gdn_Format::Text($Event['Title']).'</h3>
            <span class="Event-Time MItem">5pm</span>
            <p class="Event-Description"'.Gdn_Format::Text($Event['Description']).'</p>
         </li>';
      }
      echo '</ul>';
   }   
}
endif;


if (!function_exists('WriteGroupBanner')) :
/**
 * Output optional group banner as a div background image to allow dynamic page resizing.
 */  
function WriteGroupBanner() {
   $Group = Gdn::Controller()->Data('Group');
   
   if ($Group['Banner']) {
      echo Wrap('', 'div', array(
         'class' => 'Group-Banner',
         'style' => 'background-image: url("'.Gdn_Upload::Url($Group['Banner']).'");')
      );
   }
}
endif;


if (!function_exists('WriteGroupButtons')) :
/**
 * Output action buttons to join/apply to group.
 */
function WriteGroupButtons() {
   
   $Button = Anchor(T('JoinGroupButton', 'Join This Group'), '/group/join', 'Button BigButton Primary Group-JoinButton');
   
   echo Wrap($Button, 'div', array('class' => 'Group-Buttons'));
}
endif;


if (!function_exists('WriteGroupCards')) :
/**
 * Write a list of groups out as cards.
 * 
 * @param array $Groups
 */
function WriteGroupCards($Groups) {
   decho($Groups, 'Group Cards');
}
endif;


if (!function_exists('WriteGroupIcon')) :
/**
 * Output group icon image.
 */
function WriteGroupIcon() {
   $Group = Gdn::Controller()->Data('Group');
   
   if ($Group['Icon'])
      echo Img(Gdn_Upload::Url($Group['Icon']), array('class' => 'Group-Icon'));
}
endif;


if (!function_exists('WriteGroupList')) :
/**
 * Write a list of groups out as a list.
 * 
 * @param array $Groups
 */
function WriteGroupList($Groups) {
   
   
   if (is_array($Groups)) {
      echo '<ul class="DataList DataList-Groups">';
      
      foreach ($Groups as $Group) {
         echo '<li class="Item Item-Group">';
         
         echo Anchor(htmlspecialchars($Group['Name']), GroupUrl($Group));
         
         echo '</li>';
      }
      
      echo '</ul>';
   }
}
endif;


if (!function_exists('WriteMemberCards')) :
/**
 * Write a list of members out as cards.
 * 
 * @param array $Members
 */
function WriteMemberCards($Members) {
   if (!$Members)
      echo WriteEmptyState("No one has joined yet. Spread the word!");
   
   if (is_array($Members)) {
      foreach ($Members as $Member) {
         echo Gdn_Format::Text($Member['Name']).', ';
      } 
   }
}
endif;


if (!function_exists('WriteMemberGrid')) :
/**
 * Output just the linked user photos in rows.
 * 
 * @param array $Members
 */
function WriteMemberGrid($Members) {
   echo '<div class="PhotoGrid PhotoGridSmall">';
         
   echo '</div>';
}
endif;


if (!function_exists('WriteMemberSimpleList')) :
/**
 * Output just the photo and linked username in a column.
 * 
 * @param array $Members
 */
function WriteMemberSimpleList($Members) {
   //decho($Members, 'Member Simple List');
}
endif;