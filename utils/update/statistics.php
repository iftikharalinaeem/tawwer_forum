#!/usr/bin/php
<?php

require_once('foreach.php');

define("VERBOSE", TRUE);
define("LAME", FALSE);

$Tasks->Run(TaskList::MODE_CHUNKED, array(
   'stats/statistics'
));
