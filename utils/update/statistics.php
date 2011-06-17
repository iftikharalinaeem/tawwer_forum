#!/usr/bin/php
<?php

/**
 * This file is part of Runner.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

require_once('foreach.php');

define("VERBOSE", TRUE);
define("LAME", FALSE);

$Tasks->Run(TaskList::MODE_CHUNKED, array(
   'stats/statistics'
));
