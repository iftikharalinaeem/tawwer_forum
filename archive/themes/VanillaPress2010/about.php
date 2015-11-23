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
$ThemeInfo['VanillaPress2010'] = array(
   'Name' => 'VanillaPress2010',
   'Description' => "A Vanilla/WordPress bundle",
   'Version' => '1.0',
   'Author' => "Brendan Sera-Shriar",
   'AuthorEmail' => 'brendan@vanillaforums.com',
   'AuthorUrl' => 'http://brendanserashriar.com',
   'Options' => array(
		'Description' => 'To add change the top banner link to an external image using <img src="http://www.yourdomain.com/banner.jpg">. Make sure the image dimensions are 940px x 198px. Find out more on <a href="http://www.vanillaforums.com/blog/help-tutorials/how-to-use-theme-options">"Theme Options"</a>.',			  
					  
         'Text' => array(
            'Banner' => 'Paste image link here for top banner.'),
	)
);
   
   