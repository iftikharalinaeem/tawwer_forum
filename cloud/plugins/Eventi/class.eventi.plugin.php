<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2009-2017 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Changelog:
 *
 * @version 1.0.0 - 1.0.1
 * @author Tim Gunter <tim@vanillaforums.com>
 *
 * - Original functionality and UI.
 *
 * @version 2.0.0
 * @author Dane MacMillan <dane@vanillaforums.com>
 * @date November 3, 2013
 *
 * - Removed JavaScript dependency, so CSS tooltip will appear without JS.
 * - Removed images.
 * - Complete rewrite of HTML, CSS, and JavaScript.
 * - Completely redesigned UI.
 * - Cleaned up PHP to more easily view markup struture.
 * - JavaScript viewport edge detection (either beyond right or bottom edge of
 *   viewport), so tooltip always in visible viewport, by pushing it to the left
 *   and/or up.
 * - Event name text auto-selected upon every tooltip display, so can now
 *   easily copy name without manual selection, as well every hover if lost
 *   selection.
 * - Numbered arguments list.
 * - Long event names will produce ellipsis, though still fully selectable.
 * - Filtered out PHP events that break JavaScript and asynchronous calls,
 *   so Eventi can now always be enabled and the site will not break anymore.
 * - Can now move mouse over tooltip without fighting for focus position, so
 *   any text in the tooltip can now be selected.
 * - JavaScript delegates Eventi attached events, so newly added eventi elements
 *   still fire their events, despite being added asynchronously or post-load.
 * - Control panel: option to show or hide Eventi flags on the fly, by checking
 *   the option to the bottom-right of the page, and is remembered between
 *   refreshes. The reason for this functionality is to prevent the dev from
 *   flipping back and forth between the dashboard to enable/disable the plugin.
 *   Now the plugin can just be left enabled, and hidden or shown when needed,
 *   easily from any page. This is now possible due to Ajax fix mentioned above.
 * - Added minor delay to tooltip, so that when moving mouse over flags quickly,
 *   they do not immediately display.
 * - Arrays are now printed to HTML title attributes, so hover over any
 *   argument in the list which states it is an array to reveal the contents.
 *   Not all array information can be seen on large dumps, but it doesn't need
 *   to be all available. It's just a hint.
 * - Strings are now passed through htmlentities and sliced, as some may contain
 *   HTML, which affects the output of the tooltip.
 * - Unique fragment identifiers added for every event flag, to easily link
 *   to flags in the page by appending the URL with #eventi-MD5String, and if
 *   on page with flag targeted, it will be disabled on hover of itself.
 */

class EventiPlugin extends Gdn_Plugin {

   public function base_render_before($sender) {
      $sender->addCssFile('eventi.css', 'plugins/Eventi');
      $sender->addJsFile('eventi.js', 'plugins/Eventi');
   }

   public function base_all_handler($sender, $args, $key) {

      // These events break all asynchronous functionality on the site, as
      // they insert themselves directly in a response, so do nothing with them.
      // Without this fix, having the plugin enabled makes it difficult to use
      // the site properly, essentially requiring user to enable then disable it
      // the moment they find the information they want.
      // There may be others.
      $ajaxBreakEvents = [
          'Gdn_Controller_Finalize_Handler',
          'Gdn_Dispatcher_Cleanup_Handler'
      ];

      if (in_array($key, $ajaxBreakEvents)) {
         return;
      }

      $caller = $sender->EventArguments['WildEventStack'];
      $callerFile = str_replace(Gdn::request()->getValue('DOCUMENT_ROOT'), '', $caller['file']);

      $object = getValue('object', $caller, '');

      if (is_object($object)) {
         $object = get_class($object);
      }

      if (strlen($object)) {
         $object .= "::";
      }

      $argList = [];

      foreach ($caller['args'] as $arg) {
         if (is_object($arg)) {
            $argList[] = get_class($arg);
         } elseif (is_array($arg)) {
            $argList[] = 'array{'. sizeof($arg) .'}';
         } elseif (is_string($arg) || is_numeric($arg)) {
            $argList[] = "'". $arg ."'";
         } elseif (is_bool($arg)) {
            $argList[] = "b". (string)$arg;
         } else {
            $argList[] = $arg;
         }
      }

      $htmlArgsList = '';
      // EventArguments List
      if (sizeof($sender->EventArguments) > 1) {

         $htmlArgsList .= '
            <strong>Arguments</strong>
            <ol>';

               foreach ($sender->EventArguments as $argKey => $argValue) {
                  if ($argKey == "WildEventStack") continue;

                  if (is_object($argValue)) {
                     $argValue = get_class($argValue);
                  } elseif (is_array($argValue)) {
                     // Dump the results of array to HTML title attribute
                     $argValue = '<span title="'. htmlentities(print_r($argValue, true)) .'">array{'.sizeof($argValue).'}</span>';
                  } elseif (is_string($argValue) || is_numeric($argValue)) {
                     // Clean up strings, as some may contain HTML, which
                     // ruins the output in the toltip.
                     $stringClean = htmlentities($argValue);
                     $argValue = '<span title="'. $stringClean .'">\''. sliceString($stringClean, 1000) .'\'</span>';
                  } elseif (is_bool($argValue)) {
                     $argValue = "b".(string)$argValue;
                  }

                  $htmlArgsList .= '<li><em>'. $argKey .':</em> '. $argValue .'</li>';
               }

         $htmlArgsList .= '
            </ol>';
      }

      $callerFileAndLine = $callerFile .':'. $caller['line'];

      // This is used so that Eventi events can be linked to and shared, using
      // Url fragment identifiers.
      $eventiHtmlFragmentId = md5($htmlArgsList . $callerFileAndLine);

      $html = '
      <div class="eventi" id="eventi-'. $eventiHtmlFragmentId .'">
         <span class="eventi-flag-hook"></span>
         <div class="eventi-tooltip">
            <input type="text" class="eventi-event-name" value="'. $key .'" readonly="readonly" title="&#8984;/Ctrl+C to copy event name. It\'s auto-selected on every tooltip." />
            <div class="eventi-tooltip-body">
               <div class="eventi-subhead">
                  <div class="eventi-fire-event">'. $object . $caller['function'] .' ('. implode(',', $argList). ')</div>
                  <div class="eventi-file-line">'. $callerFileAndLine .'</div>
               </div>';

               $html .= $htmlArgsList;

            $html .= '
            </div><!--/.eventi-tooltip-body-->
            <input type="text" class="eventi-share-link" readonly="readonly" value="'. Gdn::request()->url('', true) .'#eventi-'. $eventiHtmlFragmentId .'" title="Eventi URL fragment identifier." />
         </div><!--/.eventi-tooltip-->
      </div><!--/.eventi-->';

      echo $html;
   }
}
