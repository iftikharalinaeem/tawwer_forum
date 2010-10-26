#!/usr/bin/php
<?php

require_once("includes/foreach.php");

$Tasks->Run(TaskList::MODE_CHUNKED, array(
   'global/backup',
   'global/offline',
   'global/uncache',
   'maintain/filesystem',
   'maintain/structure',
   'global/online'
));