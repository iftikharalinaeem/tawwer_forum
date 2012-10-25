<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Renders the best of filter menu
 */
class BestOfFilterModule extends Gdn_Module {
   
   public function AssetTarget() {
      return 'Panel';
   }
   
   private function _Button($Name, $Code, $CurrentReactionType) {
      $LCode = strtolower($Code);
      $Url = Url("/bestof/$LCode");
      $CssClass = $Code;
      if ($CurrentReactionType == $LCode)
         $CssClass .= ' Active';

      return '<li class="BestOf'.$CssClass.'"><a href="'.$Url.'"><span class="ReactSprite React'.$Code.'"></span> '.$Name.'</a></li>';
   }
   

   public function ToString() {
      $Controller = Gdn::Controller();
      $CurrentReactionType = $Controller->Data('CurrentReaction');
      $ReactionTypeData = $Controller->Data('ReactionTypes');
      $FilterMenu = '';
      $FilterMenu .= '<div class="BoxFilter BoxBestOfFilter">';
         $FilterMenu .= '<ul class="FilterMenu">';
            $FilterMenu .= $this->_Button(T('Everything'), 'Everything', $CurrentReactionType);
            foreach ($ReactionTypeData as $Key => $ReactionType) {
               $FilterMenu .= $this->_Button(T(GetValue('Name', $ReactionType, '')), GetValue('UrlCode', $ReactionType, ''), $CurrentReactionType);
            }      
         $FilterMenu .= '</ul>';
      $FilterMenu .= '</div>';
      return $FilterMenu;
   }
}