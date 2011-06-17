#!/usr/bin/php
<?php

/**
 * This file is part of Runner.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

require_once("includes/foreach.php");

$Tasks->Run(TaskList::MODE_CHUNKED, array(
   'global/offline',
   'global/uncache',
   'maintain/filesystem',
   'global/online'
));