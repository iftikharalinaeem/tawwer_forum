<?php

/**
 * QuickAPI Application Gateway
 * 
 * Lightweight API framework.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package quickapi
 * @since 1.0
 */

define('APP', 'LDAP2API');
define('APP_VERSION', '1.0a');

// Report and track all errors.

error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

// Define the constants we need to get going.

define('PATH_ROOT', getcwd());

// Include required files

require_once PATH_ROOT.'/library/functions.core.php';
Api::Configure();

// Dispatch

Api::Dispatch();