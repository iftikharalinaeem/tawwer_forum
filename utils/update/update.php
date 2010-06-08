#!/usr/bin/php
<?php

require_once('foreach.php');

$Tasks = new TaskList('update','/srv/www/vhosts');
$Tasks->RunAll();