<?php if (!defined('APPLICATION')) exit();

if (!function_exists('WriteEventCard')) :
/**
 * Write an event card
 * 
 * @param array $Event
 */
function WriteEventCard($Event) {
   $DateStarts = new DateTime($Event['DateStarts']);
   echo 
   '<li class="Event">
      '.DateTile($DateStarts->format('Y-m-d')).'
      <h3 class="Event-Title">'.Anchor(Gdn_Format::Text($Event['Name']), EventUrl($Event)).' <span class="Event-Time MItem">'.$DateStarts->format('g:ia').'</span></h3>

      <div class="Event-Location">'.Gdn_Format::Text($Event['Location']).'</div>
      <p class="Event-Description"'.SliceParagraph(Gdn_Format::Text($Event['Body']), 100).'</p>
   </li>';
}
endif;