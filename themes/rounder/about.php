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
$ThemeInfo['rounder'] = array(
   'Name' => 'Rounder',
   'Description' => "Black background, rounded edges.",
   'Version' => '2.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
   'Options' => array(
		'Description' => 'You can customize the heading text for <strong>Rounder</strong> using theme options.',
      'Text' => array('Header Text' => 'This is just place holder text. Use the theme options to put whatever you want here!'),
	),
   'Archived' => TRUE
);
   
   