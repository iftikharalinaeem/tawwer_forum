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
 * An associative array of information about this application.
 */
$ThemeInfo['minalla'] = array(
   'Name' => 'Minalla',
   'Description' => "A minimilist forum - simple and clean",
   'Version' => '1.0',
   'Author' => "Brendan Sera-Shriar",
   'AuthorEmail' => 'brendan@vanillaforums.com',
   'AuthorUrl' => 'http://brendanserashriar.com',
   'Options' => array(
         'Description' => 'Minalla has a bunch of options.',

         'Styles' => array(
            'Minalla Blue' => array('%s_blue', 'Description' => 'This is a blue theme'),
            'Minalla Green' => '%s_green',
            'Minalla Red' => '%s_red',
            'Minalla Grey' => '%s_grey',
            'Minalla Yellow' => '%s'),
         'Text' => array(
            'Some Text' => 'TextArea',
            'Some Other Text' => array('TextBox', 'Description' => 'This is some other text'))
   )
);