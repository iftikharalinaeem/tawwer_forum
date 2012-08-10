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
$ThemeInfo['WordPressBridge'] = array(
   'Name' => 'WordPressBridge',
   'Description' => "This theme takes your WordPress theme and pulls uses it around your Vanilla Forum.",
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
   'Options' => array(
		'Description' => 'Define where your wordpress installation is so Vanilla can grab your template files. You must have the VanillaThemeBridge plugin for wordpress enabled on your WordPress installation in order for this to function properly.',			  
      'Text' => array('WordPressUrl' => 'Your WordPress Url: http://yourdomain.com/wordpress')
	)
);
   
   