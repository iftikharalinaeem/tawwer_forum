<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2009 Mark O'Sullivan
This file is part of the QuickIn plugin for Vanilla 2.
The QuickIn plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The QuickIn plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with the Vanilla QuickIn plugin.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] vanillaforums [dot] com

Include this file in your external application above your calls to VanillaQuickIn();
*/

function VanillaQuickIn($VanillaUrl, $UniqueID, $Email, $Name, $Attributes) {
   $VanillaUrl .= substr($VanillaUrl, -1, 1) == '/' ? '' : '/';
   $VanillaUrl .= 'plugin/quickin/';
   $VanillaUrl .= '?UniqueID='.$UniqueID;
   $VanillaUrl .= '&Email='.$Email;
   $VanillaUrl .= '&Name='.$Name;
   if (is_array($Attributes))
      $VanillaUrl .= '&Attributes=arr:'.json_encode($Attributes);
   
   ?>
   <script type="text/javascript">
      var ajax = new XMLHttpRequest();
      ajax.open('GET', '<?php echo $VanillaUrl; ?>', false);
      ajax.send(null);
   </script>
   <?php
}