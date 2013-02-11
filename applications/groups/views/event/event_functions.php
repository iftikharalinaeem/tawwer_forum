<?php if (!defined('APPLICATION')) exit();


if (!function_exists('WriteEventButtons')) :
/**
 * Output action buttons to edit/delete an Event.
 * 
 * @param array $Event Optional. Uses data array's Event if none is provided. 
 */
function WriteEventButtons($Event = NULL) {
   if (!$Event)
      $Event = Gdn::Controller()->Data('Event');
   if (!$Event) 
      return;
   
   echo '<div class="Event-Buttons">';
   
   if (Gdn::Session()->IsValid() && !EventPermission('Member', $Event)) {
      if (EventPermission('Join', $Event)) {
         echo ' '.Anchor(T('Join this Event'), EventUrl($Event, 'join'), 'Button Primary Event-JoinButton Popup').' ';
      } else {
         echo ' '.Wrap(T('Join this Event'), 'span', array('class' => 'Button Primary Event-JoinButton Disabled', 'title' => EventPermission('Join.Reason', $Event))).' ';
      }
   }
      
   $Options = array();
   
   if (EventPermission('Edit', $Event)) {
      $Options['Edit'] = array('Text' => T('Edit'), 'Url' => EventUrl($Event, 'edit'));
   }
//   if (EventPermission('Delete')) {
//      $Options['Delete'] = array('Text' => T('Delete'), 'Url' => EventUrl($Event, 'delete'));
//   }
   if (EventPermission('Leave', $Event)) {
      $Options['Leave'] = array('Text' => T('Leave Event'), 'Url' => EventUrl($Event, 'leave'), 'CssClass' => 'Popup');
   }

   if (count($Options))
      echo ButtonDropDown($Options, 'Button DropRight Event-OptionsButton', Sprite('SpOptions', 'Sprite16'));
   
   echo '</div>';
}
endif;


if (!function_exists('WriteEventCard')) :
/**
 * Write an event card
 * 
 * Optionally write rich listing
 * 
 * @param array $Event
 */
function WriteEventCard($Event) {
   $DateStarts = new DateTime($Event['DateStarts']);
   echo '<div class="Event">';
   if (GetValue('Rich', $Event)) {
      
      
      
   } else {
      
      echo DateTile($DateStarts->format('Y-m-d')).'
         <h3 class="Event-Title">'.Anchor(Gdn_Format::Text($Event['Name']), EventUrl($Event)).' <span class="Event-Time MItem">'.$DateStarts->format('g:ia').'</span></h3>

         <div class="Event-Location">'.Gdn_Format::Text($Event['Location']).'</div>
         <p class="Event-Description">'.SliceParagraph(Gdn_Format::Text($Event['Body']), 100).'</p>';
      
   }
   echo '</div>';
}
endif;


if (!function_exists('WriteEventCards')) :
/**
 * Write a list of events out as cards.
 * 
 * @param array $Events
 * @param string $EmptyMessage
 */
function WriteEventCards($Events, $EmptyMessage = '') {
   if (!$Events)
      WriteEmptyState($EmptyMessage);
   else {
      echo '<div class="Cards Cards-Events">';
      foreach ($Events as $Event) {
         WriteEventCard($Event);
      }
      echo '</div>';
   }
}
endif;