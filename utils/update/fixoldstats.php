#!/usr/bin/php
<?php

require_once("includes/foreach.php");

$Tasks->Run(TaskList::MODE_CHUNKED, array(
   'update/fixoldstats'
));