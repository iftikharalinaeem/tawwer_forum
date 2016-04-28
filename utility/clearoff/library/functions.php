<?php

/**
 * This file is part of DiscussionStats.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010-2014 Vanilla Forums Inc
 */

/**
 * Return the value from an associative array or an object.
 *
 * @param string $Key The key or property name of the value.
 * @param mixed $Collection The array or object to search.
 * @param mixed $Default The value to return if the key does not exist.
 * @param bool $Remove Whether or not to remove the item from the collection.
 * @return mixed The value from the array or object.
 */
function GetValue($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
    $Result = $Default;
    if (is_array($Collection) && array_key_exists($Key, $Collection)) {
        $Result = $Collection[$Key];
        if ($Remove)
            unset($Collection[$Key]);
    } elseif (is_object($Collection) && property_exists($Collection, $Key)) {
        $Result = $Collection->$Key;
        if ($Remove)
            unset($Collection->$Key);
    }

    return $Result;
}

/**
 * Return the value from an associative array or an object.
 * This function differs from GetValue() in that $Key can be a string consisting of dot notation that will be used to recursivly traverse the collection.
 *
 * @param string $Key The key or property name of the value.
 * @param mixed $Collection The array or object to search.
 * @param mixed $Default The value to return if the key does not exist.
 * @return mixed The value from the array or object.
 */
function GetValueR($Key, $Collection, $Default = FALSE) {
    $Path = explode('.', $Key);

    $Value = $Collection;
    for ($i = 0; $i < count($Path); ++$i) {
        $SubKey = $Path[$i];

        if (is_array($Value) && isset($Value[$SubKey])) {
            $Value = $Value[$SubKey];
        } elseif (is_object($Value) && isset($Value->$SubKey)) {
            $Value = $Value->$SubKey;
        } else {
            return $Default;
        }
    }
    return $Value;
}

/**
 * Generate a random string
 *
 * @param integer $Length Desired length of result string
 * @param string $Characters Character set
 * @return string
 */
function RandomString($Length, $Characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
    $CharLen = strlen($Characters) - 1;
    $String = '';
    for ($i = 0; $i < $Length; ++$i) {
        $Offset = rand() % $CharLen;
        $String .= substr($Characters, $Offset, 1);
    }
    return $String;
}

/**
 * Formats a string by inserting data from its arguments, similar to sprintf, but with a richer syntax.
 *
 * @param string $String The string to format with fields from its args enclosed in curly braces. The format of fields is in the form {Field,Format,Arg1,Arg2}. The following formats are the following:
 *  - date: Formats the value as a date. Valid arguments are short, medium, long.
 *  - number: Formats the value as a number. Valid arguments are currency, integer, percent.
 *  - time: Formats the valud as a time. This format has no additional arguments.
 *  - url: Calls Url() function around the value to show a valid url with the site. You can pass a domain to include the domain.
 *  - urlencode, rawurlencode: Calls urlencode/rawurlencode respectively.
 *  - html: Calls htmlspecialchars.
 * @param array $Args The array of arguments. If you want to nest arrays then the keys to the nested values can be seperated by dots.
 * @return string The formatted string.
 * <code>
 * echo FormatString("Hello {Name}, It's {Now,time}.", array('Name' => 'Frank', 'Now' => '1999-12-31 23:59'));
 * // This would output the following string:
 * // Hello Frank, It's 12:59PM.
 * </code>
 */
function FormatString($String, $Args = array()) {
    _FormatStringCallback($Args, TRUE);
    $Result = preg_replace_callback('/{([^}]+?)}/', '_FormatStringCallback', $String);

    return $Result;
}

function _FormatStringCallback($Match, $SetArgs = FALSE) {
    static $Args = array();
    if ($SetArgs) {
        $Args = $Match;
        return;
    }

    $Match = $Match[1];
    if ($Match == '{')
        return $Match;

    // Parse out the field and format.
    $Parts = explode(',', $Match);
    $Field = trim($Parts[0]);
    $Format = strtolower(trim(GetValue(1, $Parts, '')));
    $SubFormat = strtolower(trim(GetValue(2, $Parts, '')));
    $FomatArgs = GetValue(3, $Parts, '');

    if (in_array($Format, array('currency', 'integer', 'percent'))) {
        $FormatArgs = $SubFormat;
        $SubFormat = $Format;
        $Format = 'number';
    } elseif (is_numeric($SubFormat)) {
        $FormatArgs = $SubFormat;
        $SubFormat = '';
    }

    $Value = GetValueR($Field, $Args, '');
    if ($Value == '' && $Format != 'url') {
        $Result = '';
    } else {
        switch (strtolower($Format)) {
            case 'date':
                $TimeValue = strtotime($Value);
                switch ($SubFormat) {
                    case 'short':
                        $Result = date($TimeValue, 'd/m/Y');
                        break;
                    case 'medium':
                        $Result = date($TimeValue, 'j M Y');
                        break;
                    case 'long':
                        $Result = date($TimeValue, 'j F Y');
                        break;
                    default:
                        $Result = date($TimeValue);
                        break;
                }
                break;
            case 'html':
            case 'htmlspecialchars':
                $Result = htmlspecialchars($Value);
                break;
            case 'number':
                if (!is_numeric($Value)) {
                    $Result = $Value;
                } else {
                    switch ($SubFormat) {
                        case 'currency':
                            $Result = '$' . number_format($Value, is_numeric($FormatArgs) ? $FormatArgs : 2);
                        case 'integer':
                            $Result = (string)round($Value);
                            if (is_numeric($FormatArgs) && strlen($Result) < $FormatArgs) {
                                $Result = str_repeat('0', $FormatArgs - strlen($Result)) . $Result;
                            }
                            break;
                        case 'percent':
                            $Result = round($Value * 100, is_numeric($FormatArgs) ? $FormatArgs : 0);
                            break;
                        default:
                            $Result = number_format($Value, is_numeric($FormatArgs) ? $FormatArgs : 0);
                            break;
                    }
                }
                break;
            case 'rawurlencode':
                $Result = rawurlencode($Value);
                break;
            case 'time':
                $TimeValue = strtotime($Value);
                $Result = date($TimeValue, 'H:ia');
                break;
            case 'url':
                if (strpos($Field, '/') !== FALSE)
                    $Value = $Field;
                $Result = Url($Value, $SubFormat == 'domain');
                break;
            case 'urlencode':
                $Result = urlencode($Value);
                break;
            default:
                $Result = $Value;
                break;
        }
    }
    return $Result;
}

/** Checks whether or not string A begins with string B.
 *
 * @param string $Haystack The main string to check.
 * @param string $Needle The substring to check against.
 * @param bool $CaseInsensitive Whether or not the comparison should be case insensitive.
 * @param bool Whether or not to trim $B off of $A if it is found.
 * @return bool|string Returns true/false unless $Trim is true.
 */
function StringBeginsWith($Haystack, $Needle, $CaseInsensitive = FALSE, $Trim = FALSE) {
    if (strlen($Haystack) < strlen($Needle))
        return $Trim ? $Haystack : FALSE;
    elseif (strlen($Needle) == 0) {
        if ($Trim)
            return $Haystack;
        return TRUE;
    } else {
        $Result = substr_compare($Haystack, $Needle, 0, strlen($Needle), $CaseInsensitive) == 0;
        if ($Trim)
            $Result = $Result ? substr($Haystack, strlen($Needle)) : $Haystack;
        return $Result;
    }
}

/** Checks whether or not string A ends with string B.
 *
 * @param string $Haystack The main string to check.
 * @param string $Needle The substring to check against.
 * @param bool $CaseInsensitive Whether or not the comparison should be case insensitive.
 * @param bool Whether or not to trim $B off of $A if it is found.
 * @return bool|string Returns true/false unless $Trim is true.
 */
function StringEndsWith($Haystack, $Needle, $CaseInsensitive = FALSE, $Trim = FALSE) {
    if (strlen($Haystack) < strlen($Needle)) {
        return $Trim ? $Haystack : FALSE;
    } elseif (strlen($Needle) == 0) {
        if ($Trim)
            return $Haystack;
        return TRUE;
    } else {
        $Result = substr_compare($Haystack, $Needle, -strlen($Needle), strlen($Needle), $CaseInsensitive) == 0;
        if ($Trim)
            $Result = $Result ? substr($Haystack, 0, -strlen($Needle)) : $Haystack;
        return $Result;
    }
}

/**
 * Takes an array of path parts and concatenates them using the specified
 * delimiter. Delimiters will not be duplicated. Example: all of the
 * following arrays will generate the path "/path/to/vanilla/applications/dashboard"
 * array('/path/to/vanilla', 'applications/dashboard')
 * array('/path/to/vanilla/', '/applications/dashboard')
 * array('/path', 'to', 'vanilla', 'applications', 'dashboard')
 * array('/path/', '/to/', '/vanilla/', '/applications/', '/dashboard')
 *
 * @param array $Paths The array of paths to concatenate.
 * @param string $Delimiter The delimiter to use when concatenating. Defaults to system-defined directory separator.
 * @returns The concatentated path.
 */
function CombinePaths($Paths, $Delimiter = '/') {
    if (is_array($Paths)) {
        $MungedPath = implode($Delimiter, $Paths);
        $MungedPath = str_replace(array($Delimiter . $Delimiter . $Delimiter, $Delimiter . $Delimiter), array($Delimiter, $Delimiter), $MungedPath);
        return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $MungedPath);
    } else {
        return $Paths;
    }
}

if (!function_exists('paths')) {
   /**
    * Concatenate path elements into single string
    *
    * Takes a variable number of arguments and concatenates them. Delimiters will
    * not be duplicated. Example: all of the following invocations will generate
    * the path "/path/to/vanilla/applications/dashboard"
    *
    * '/path/to/vanilla', 'applications/dashboard'
    * '/path/to/vanilla/', '/applications/dashboard'
    * '/path', 'to', 'vanilla', 'applications', 'dashboard'
    * '/path/', '/to/', '/vanilla/', '/applications/', '/dashboard'
    *
    * @param function arguments
    * @return the concatentated path.
    */
   function paths() {
      $paths = func_get_args();
      $delimiter = '/';
      if (is_array($paths)) {
         $mungedPath = implode($delimiter, $paths);
         $mungedPath = str_replace(array($delimiter.$delimiter.$delimiter, $delimiter.$delimiter), array($delimiter, $delimiter), $mungedPath);
         return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $mungedPath);
      } else {
         return $paths;
      }
   }
}

if (!function_exists('val')) {
   /**
    * Return the value from an associative array or an object.
    *
    * @param string $key The key or property name of the value.
    * @param mixed $collection The array or object to search.
    * @param mixed $default The value to return if the key does not exist.
    * @return mixed The value from the array or object.
    */
   function val($key, $collection, $default = false) {
      if (is_array($collection) && array_key_exists($key, $collection)) {
         return $collection[$key];
      } elseif (is_object($collection) && property_exists($collection, $key)) {
         return $collection->$key;
      }
      return $default;
   }
}

if (!function_exists('valr')) {
   /**
    * Return the value from an associative array or an object.
    * This function differs from GetValue() in that $Key can be a string consisting of dot notation that will be used to recursivly traverse the collection.
    *
    * @param string $key The key or property name of the value.
    * @param mixed $collection The array or object to search.
    * @param mixed $default The value to return if the key does not exist.
    * @return mixed The value from the array or object.
    */
   function valr($key, $collection, $default = false) {
      $path = explode('.', $key);

      $value = $collection;
      for ($i = 0; $i < count($path); ++$i) {
         $subKey = $path[$i];

         if (is_array($value) && isset($value[$subKey])) {
            $value = $value[$subKey];
         } elseif (is_object($value) && isset($value->$subKey)) {
            $value = $value->$subKey;
         } else {
            return $default;
         }
      }
      return $value;
   }
}

if (!function_exists('svalr')) {
   /**
    * Set a key to a value in a collection
    *
    * Works with single keys or "dot" notation. If $key is an array, a simple
    * shallow array_merge is performed.
    *
    * @param string $key The key or property name of the value.
    * @param array $collection The array or object to search.
    * @param type $value The value to set
    * @return mixed Newly set value or if array merge
    */
   function svalr($key, &$collection, $value = null) {
      if (is_array($key)) {
         $collection = array_merge($collection, $key);
         return null;
      }

      if (strpos($key,'.')) {
         $path = explode('.', $key);

         $selection = &$collection;
         $mx = count($path) - 1;
         for ($i = 0; $i <= $mx; ++$i) {
            $subSelector = $path[$i];

            if (is_array($selection)) {
               if (!isset($selection[$subSelector])) {
                  $selection[$subSelector] = array();
               }
               $selection = &$selection[$subSelector];
            } else if (is_object($selection)) {
               if (!isset($selection->$subSelector)) {
                  $selection->$subSelector = new stdClass();
               }
               $selection = &$selection->$subSelector;
            } else {
               return null;
            }
         }
         return $selection = $value;
      } else {
         if (is_array($collection)) {
            return $collection[$key] = $value;
         } else {
            return $collection->$key = $value;
         }
      }
   }
}

/**
 * Recursively delete a folder and subfolders
 *
 * @param string $Path
 * @return void
 */
function RemoveFolder($Path) {
    if (!file_exists($Path))
        return;

    if (is_file($Path)) {
        unlink($Path);
        return;
    }

    $Path = rtrim($Path, '/') . '/';

    // Get all of the files in the directory.
    if ($dh = opendir($Path)) {
        while (($File = readdir($dh)) !== false) {
            if (trim($File, '.') == '')
                continue;

            $SubPath = $Path . $File;

            if (is_dir($SubPath))
                RemoveFolder($SubPath);
            else
                unlink($SubPath);
        }
        closedir($dh);
    }
    rmdir($Path);
}

/**
 * A Vanilla wrapper for php's parse_url, which doesn't always return values for every url part.
 *
 * @param string $Url The url to parse.
 * @param constant Use PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_USER, PHP_URL_PASS, PHP_URL_PATH, PHP_URL_QUERY or PHP_URL_FRAGMENT to retrieve just a specific url component.
 * @return array or string
 */
function ParseUrl($Url, $Component = -1) {
    // Retrieve all the parts
    $PHP_URL_SCHEME = @parse_url($Url, PHP_URL_SCHEME);
    $PHP_URL_HOST = @parse_url($Url, PHP_URL_HOST);
    $PHP_URL_PORT = @parse_url($Url, PHP_URL_PORT);
    $PHP_URL_USER = @parse_url($Url, PHP_URL_USER);
    $PHP_URL_PASS = @parse_url($Url, PHP_URL_PASS);
    $PHP_URL_PATH = @parse_url($Url, PHP_URL_PATH);
    $PHP_URL_QUERY = @parse_url($Url, PHP_URL_QUERY);
    $PHP_URL_FRAGMENT = @parse_url($Url, PHP_URL_FRAGMENT);

    // Build a cleaned up array to return
    $Parts = array(
        'scheme' => $PHP_URL_SCHEME == NULL ? 'http' : $PHP_URL_SCHEME,
        'host' => $PHP_URL_HOST == NULL ? '' : $PHP_URL_HOST,
        'port' => $PHP_URL_PORT == NULL ? $PHP_URL_SCHEME == 'https' ? '443' : '80' : $PHP_URL_PORT,
        'user' => $PHP_URL_USER == NULL ? '' : $PHP_URL_USER,
        'pass' => $PHP_URL_PASS == NULL ? '' : $PHP_URL_PASS,
        'path' => $PHP_URL_PATH == NULL ? '' : $PHP_URL_PATH,
        'query' => $PHP_URL_QUERY == NULL ? '' : $PHP_URL_QUERY,
        'fragment' => $PHP_URL_FRAGMENT == NULL ? '' : $PHP_URL_FRAGMENT
    );

    // Return
    switch ($Component) {
        case PHP_URL_SCHEME: return $Parts['scheme'];
        case PHP_URL_HOST: return $Parts['host'];
        case PHP_URL_PORT: return $Parts['port'];
        case PHP_URL_USER: return $Parts['user'];
        case PHP_URL_PASS: return $Parts['pass'];
        case PHP_URL_PATH: return $Parts['path'];
        case PHP_URL_QUERY: return $Parts['query'];
        case PHP_URL_FRAGMENT: return $Parts['fragment'];
        default: return $Parts;
    }
}

/**
 * Complementary to ParseUrl, this function puts the pieces back together and returns a valid url.
 *
 * @param array ParseUrl array to build.
 * @return string
 */
function BuildUrl($Parts) {
    // Full format: http://user:pass@hostname:port/path?querystring#fragment
    $Return = $Parts['scheme'] . '://';
    if ($Parts['user'] != '' || $Parts['pass'] != '')
        $Return .= $Parts['user'] . ':' . $Parts['pass'] . '@';

    $Return .= $Parts['host'];
    // Custom port?
    if ($Parts['port'] == '443' && $Parts['scheme'] == 'https') {

    } elseif ($Parts['port'] == '80' && $Parts['scheme'] == 'http') {

    } elseif ($Parts['port'] != '') {
        $Return .= ':' . $Parts['port'];
    }

    if ($Parts['path'] != '') {
        if (substr($Parts['path'], 0, 1) != '/')
            $Return .= '/';
        $Return .= $Parts['path'];
    }
    if ($Parts['query'] != '')
        $Return .= '?' . $Parts['query'];

    if ($Parts['fragment'] != '')
        $Return .= '#' . $Parts['fragment'];

    return $Return;
}

function TrimServer($Server) {
    return preg_replace('/^(int|ext)\.(.+)/i', '$2', $Server);
}

function Internal($Server) {
    return 'int.' . TrimServer($Server);
}

function Server($Name, $Cluster, $Hostname = 'vanilladev.com') {
    return "{$Name}.{$Cluster}.{$Hostname}";
}

function Lock() {
    $MyPid = getmypid();

    if (Locked(LOCKFILE))
        return FALSE;
    $LockObject = fopen(LOCKFILE, 'w');
    $Locked = flock($LockObject, LOCK_EX | LOCK_NB);
    if ($Locked) {
        fwrite($LockObject, $MyPid);
        flock($LockObject, LOCK_UN);
    }

    fclose($LockObject);

    // Now sleep a bit
    $SleepLength = mt_rand(1000000, 3000000);
    $SleepLengthF = number_format($SleepLength);
    Workers::Log(Workers::LOG_L_INFO, "Sleeping for {$SleepLengthF} usec before checking lock");
    usleep($SleepLength);

    // Get Locked PID
    $LockPid = file_get_contents(LOCKFILE);
    if ($LockPid != $MyPid) {
        Workers::Log(Workers::LOG_L_INFO, "Failed to lock");
        return FALSE;
    }

    return $Locked;
}

function Unlock() {
    @unlink(LOCKFILE);
    return TRUE;
}

function Locked() {
    Workers::Log(Workers::LOG_L_INFO, "Locked state check");
    if (!file_exists(LOCKFILE))
        return FALSE;

    Workers::Log(Workers::LOG_L_INFO, "  lockfile exists");
    $LockPid = (int)trim(file_get_contents(LOCKFILE));
    $MyPid = (int)getmypid();

    // This is my lockfile, we cool dawg
    if (!$LockPid)
        return FALSE;

    Workers::Log(Workers::LOG_L_INFO, "  locked pid is {$LockPid}, my pid is {$MyPid}");
    if ($MyPid == $LockPid)
        return FALSE;

    // Is the locked pid running?
    $psExists = posix_kill($LockPid, 0);
    $psErrorRunning = !(bool)posix_get_last_error();
    Workers::Log(Workers::LOG_L_INFO, "  locked pid process is " . (($psExists) ? 'running' : 'not running'));

    // No? Unlock and return Locked=FALSE
    if (!$psExists && !$psErrorRunning) {
        Unlock();
        return FALSE;
    }

    // Someone else is already running, GTFO
    return TRUE;
}

/**
 * Takes an elapsed microtime and format it in an human readable form.
 *
 * @param $elapsedMicrotime microsite(true) - $previousMicrotime
 * @return string [(hours)h][(minutes)m](seconds).(micro)s
 */
function timeFormat($elapsedMicrotime) {
    $time = number_format($elapsedMicrotime, 3, '.', '');
    list($time, $elapsedMicrotime) = explode('.', $time);
    if ($time >= 10) {
        $elapsedMicrotime = null;
    } else {
        $elapsedMicrotime = '.'.$elapsedMicrotime;
    }
    $hours = null;
    if ($time >= 3600) {
        $hours = floor($time / 3600).'h';
        $time -= $hours * 3600;
    }
    $minutes = null;
    if ($time >= 60) {
        $minutes = str_pad(floor($time / 60), 2, '0', STR_PAD_LEFT).'m';
        $time -= $minutes * 60;
    }
    $seconds = $time;
    if ($elapsedMicrotime === null) {
        $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
    }
    return $hours.$minutes.$seconds.$elapsedMicrotime.'s';
}
