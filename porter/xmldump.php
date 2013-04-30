#!/usr/bin/php
<?php
define('APPLICATION', 'xmldump');

error_reporting(E_ALL & ~E_USER_NOTICE); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

require_once __DIR__.'/framework/bootstrap.php';
requireFeatures(FEATURE_COMMANDLINE, FEATURE_FORMATTING, FEATURE_SIMPLEHTMLDOM);

$noisewords = array("a", "about", "all", "an", "and", "any", "are", "as", "at", "be", "been", "best", "both", "by", "click", "com", "do", "does", "each", "either", "every", "facts", "few", "find", "for", "free", "from", "get", "go", "had", "has", "have", "he", "help", "how", "i", "if", "in", "inc", "into", "is", "it", "know", "lbs", "link", "make", "makes", "me", "more", "most", "my", "no", "note", "often", "on", "or", "our", "ours", "oz", "page", "since", "site", "so", "some", "take", "tbsp", "than", "that", "the", "them", "therefore", "these", "they", "to", "too", "us", "view", "was", "we", "web", "what", "when", "where", "which", "while", "who", "whose", "why", "with", "without", "you", "youre", "your", "yours");
function main() {
   $opts = array(
      'host' => array('Connect to host.', CMDLINE_SHORT => 'h', CMDLINE_DEFAULT => '127.0.0.1'),
      'database' => array('Database to use.', CMDLINE_SHORT => 'd', CMDLINE_FLAGS => CMDLINE_REQUIRED),
      'user' => array('User for login if not current user.', CMDLINE_SHORT => 'u'),
      'password' => array('Password to use when connecting to server.', CMDLINE_SHORT => 'p'),
      'mode' => array('What mode to use to dump the data.', 'valid' => array(Db::MODE_ECHO, Db::MODE_EXEC), 'default' => Db::MODE_EXEC),
      'movedir' => array('Move completed files to this directory.')
   );
   $files = array('file');
   $xodomains = array('theknot.com', 'weddingchannel.com', 'weddings.com', 'thebump.com', 'thenest.com');
   $cdn = "http://cdn.vanillaforums.com/xogrp";
   global $fileroot;
   $fileroot = '/www/xogrpfiles';
   $startTime = microtime(true);

   $options = parseCommandLine('xmldump', $opts, $files);
   
   $path = $options['file'];
   
   if (!file_exists($path)) {
      echo "File does not exist: $path\n";
      die();
   }
   
   $db = new MySqlDb('localhost', 'root', '', 'xo_imp');
   $db->px = 'GDN_z';

   $formats = array(
      'Discussion' => array(
         'columns' => array(
            'DiscussionKey.KeyWithoutForumKey' => array('ForeignID', 'type' => 'varchar(40)', 'filter' => 'stripNamespace', 'index' => Db::INDEX_PK),
            'ForumKey.KeyWithoutCategoryKey' => array('Category.ForeignID', 'type' => 'varchar(40)', 'filter' => 'stripNamespace', 'index' => Db::INDEX_FK),
            'CategoryKey.Key' => array('Category.ParentKey', 'filter' => 'stripNamespace'),
            'RowType' => array('Type', 'type' => 'varchar(255)'),
            'Body' => array('Body', 'type' => 'text'),
            'Format' => array('Format', 'type' => 'varchar(10)'),
            'Title' => array('Name'),
            'Slug' => array('Slug', 'type' => 'varchar(150)'),
            'ShortSlug' => array('ShortSlug', 'type' => 'varchar(150)'),
            'ShortTitle' => array('ShortTitle', 'type' => 'varchar(50)'),
            'IsSticky' => array('Announce', 'type' => 'tinyint'),
            'IsClosed' => array('Closed', 'type' => 'tinyint'),
            'ContentBlockingState' => array('Blocked', 'type' => 'varchar(50)'),
            'Owner.Key' => array('InsertUserKey'),
            'CreatedOn' => array('DateInserted', 'type' => 'datetime'),
            'LatestTimeStamp' => array('DateUpdated', 'type' => 'datetime'),
            'SiteOfOriginKey' => array('Site', 'filter' => 'stripSubdomain', 'index' => Db::INDEX_IX),
            '_file' => array('ImportFile'),
            'Raw' => array('Raw', 'type' => 'mediumtext'),
            'Count' => array('Count', 'type' => 'int')
            ),
         'tableoptions' => array('collate' => 'utf8_unicode_ci'),
         'rowfilter' => function(&$row) use ($cdn, $xodomains) {
            global $fileroot;
      
            $row['Body'] = extractBase64Images($row['Body'], $fileroot.'/xogrp/b64-images', "$cdn/b64-images");
            $row['Body'] = downloadImages($row['Body'], $xodomains, $fileroot.'/xogrp/downloaded', "$cdn/downloaded");
            $row['Raw'] = json_encode($row, JSON_PRETTY_PRINT);
            $row['Slug'] = formatUrl($row['Title']);
            $row['ShortSlug'] = removeNoiseWords($row['Slug']);
            $row['Count'] = 1;
            
            $row['Format'] = 'Html';
            
            $row['RowType'] = null;
            if (forceBool(val('IsPoll', $row)))
               $row['RowType'] = 'Poll';
         }),
      'Post' => array(
         'tablename' => 'Comment',
         'columns' => array(
            'Key.KeyWithoutDiscussionKey' => array('ForeignID', 'type' => 'varchar(40)', 'filter' => 'stripNamespace', 'index' => Db::INDEX_PK),
            'DiscussionKey.KeyWithoutForumKey' => array('Discussion.ForeignID', 'type' => 'varchar(40)', 'filter' => 'stripNamespace', 'required' => true),
            'Body' => array('Body', 'type' => 'text'),
            'ContentCreatedOn' => array('DateInserted', 'type' => 'datetime'),
            'Owner.Key' => array('InsertUserKey'),
            'LastUpdated' => array('DateUpdated', 'type' => 'datetime'),
            'LastEditedBy.Key' => array('UpdateUserID'),
            'ContentBlockingState' => array('Blocked', 'type' => 'varchar(50)'),
            'SiteOfOriginKey' => array('Site', 'filter' => 'stripSubdomain'),
            '_file' => array('ImportFile'),
            'Raw' => array('Raw', 'type' => 'mediumtext'),
            'Count' => array('Count', 'type' => 'int')
         ),
         'tableoptions' => array('collate' => 'utf8_unicode_ci'),
         'rowfilter' => function(&$row) use ($cdn, $xodomains) {
            global $fileroot;
            $row['Body'] = extractBase64Images($row['Body'], $fileroot.'/xogrp/b64-images', "$cdn/b64-images");
            $row['Body'] = downloadImages($row['Body'], $xodomains, $fileroot.'/xogrp/downloaded', "$cdn/downloaded");
            $row['Raw'] = json_encode($row, JSON_PRETTY_PRINT);
            $row['Count'] = 1;

            $row['Format'] = 'Html';
         }
      )
   );
         
   $db->mode = $options['mode'];
   
   // First make sure we define the tables.
   $formats = getFullFormats($formats);
   foreach ($formats as $format) {
      $tdef = $format;
      $columns = array();
      foreach ($format['columns'] as $source => $cdef) {
         $columns[$cdef[0]] = $cdef;
      }
      touchValue('name', $tdef, $tdef['tablename']);
      $tdef['columns'] = $columns;
      
      $db->defineTable($tdef, val('tableoptions', $format));
   }
   
   $movedir = val('movedir', $options);
   if ($movedir) {
      $movedir = rtrim($movedir, '/');
      if (!file_exists($movedir)) {
         mkdir($movedir, 0777, true);
         if (!file_exists($movedir)) {
            die("Count not create movedir $movedir\n");
         }
      } elseif (!is_dir($movedir)) {
         fwrite(STDERR, "The directory supplied for movedir is not a directory. ($movedir)\n");
         die();
      }
   }
   
   if (is_dir($path)) {
      $paths = scandir($path);
      
      // Sort the paths so that if they have a date somewhere we can insert most recent over top of older data..
      natcasesort($paths);
      
      $dir = rtrim($path, '/').'/';
      
      $fileroot .= '/'.basename($path);
   } else {
      $paths = (array)$path;
      $dir = '';
   }
   
   
   $total = count($paths);
   $i = 0;
   foreach ($paths as $path) {
      $path = $dir.$path;
      if (!is_file($path) || substr(basename($path), 0, 1) == '.')
         continue;
      dumpXmlFile($path, $formats, $db);
      
      if ($movedir) {
         // Move the file into the completed dir.
         rename($path, $movedir.'/'.basename($path));
      }
      
      $i++;
      $percent = $i * 100 / $total;
      if ($percent > 0) {
         $elapsed = microtime(true) - $startTime;
         $totalTime = 100 * $elapsed / $percent;
         $timeLeft = $totalTime - $elapsed;
         $timeLeft = formatTimespan($timeLeft);
      } else {
         $timeLeft = 'unknown';
      }
      fprintf(STDERR, ", %d/%d (%.1f%%, %s left)\n", $i, $total, $percent, $timeLeft);
   }
}

/**
 * 
 * @param type $path
 * @param type $formats
 * @param MySqlDb $db
 */
function dumpXmlFile($path, $formats, $db) {
   $formats = getFullFormats($formats);
   $counts = array_fill_keys(array_keys($formats), 0);
   $names = array();
   
   $filesize = (int)filesize($path);

   fwrite(STDERR, "Dumping $path $filesize\n");
   if (!filesize($path)) {
      echo "  empty\n";
      return;
   }
   
   $xml = new XmlReader();
   $opened = $xml->open($path);
   if (!$opened) {
      trigger_error("Could not open $path.", E_USER_WARNING);
      return;
   }
   
   $extraData = array('_path' => $path, '_file' => basename($path));
   
   $inserts = array();
   
   $countRead = 0;
   $countInsertRows = 0;
   $countInserted = 0;
   $countSkipped = 0;
   
   while ($xml->read()) {
      if ($xml->nodeType != XMLReader::ELEMENT)
         continue;

      $name = $xml->name;

      if (!isset($names[$name])) {
         $names[$name] = TRUE;
      }

      if (isset($formats[$name])) {
         $format = $formats[$name];
         $str = $xml->readOuterXml();
         $xml->next();
         $row = parseXmlRow($str, $format, $extraData);
         $countRead++;
         
         $table = $format['tablename'];
         
         // We need to see if there is a more recent record in the db.
         $currentRow = $db->get($table, array('ForeignID' => $row['ForeignID']));
         if ($currentRow) {
            $currentRow = array_pop($currentRow);
            if (dateCompare($currentRow['DateUpdated'], $row['DateUpdated']) >= 0) {
               if ($currentRow['Body'] != $row['Body']) {
                  fwrite(STDERR, "\n$table {$row['ForeignID']}, bodies don't match.\n");
               }
               
               $countSkipped++;
               continue;
            } else {
               $row['Count']++;
            }
         }
         
         $inserts[$table][] = $row;
         if (count($inserts[$table]) >= 10) {
            $countInsertRows += count($inserts[$table]);
            $countInserted += $db->insertMulti($table, $inserts[$table], array(Db::INSERT_REPLACE => true));            
            $inserts[$table] = array();
            fwrite(STDERR, percentDot($countInserted, $countInsertRows));
         }
      }
   }
   
   // Insert all of the remaining data.
   foreach ($inserts as $table => $rows) {
      $countInsertRows += count($rows);
      $countInserted += $db->insertMulti($table, $rows, array(Db::INSERT_REPLACE => true));
      fwrite(STDERR, percentDot($countInserted, $countInsertRows));
   }
   
   // Write the final status.
   switch ($db->mode) {
      case Db::MODE_EXEC:
         fwrite(STDERR, " $countRead read, $countInsertRows inserts sent, $countInserted inserted, $countSkipped skipped");
         break;
      case Db::MODE_ECHO:
         echo "\n-- $countRead read\n";
         break;
   }
}

function downloadImages($str, $domains, $dir, $prefix) {
//   echo "\n--\n$str\n";
   try {
      $dom = str_get_html($str);
   } catch (Exception $e) {
      fwrite(STDERR, "\nCould not parse html, continuing...\n");
      return $str;
   }
   
   if (!is_object($dom)) {
      trigger_error("Could not parse:\n$str", E_USER_NOTICE);
      return $str;
   }
   
   // Loop through all of the images in the post.
   foreach ($dom->find('img') as $img) {
      $src = $img->src;
      $urlparts = parse_url($src);
      if ($urlparts === false || !isset($urlparts['host'], $urlparts['path']))
         continue;
      
      
      $domain = strtolower(stripSubdomain($urlparts['host']));
      if (!in_array($domain, $domains)) {
         continue;
      }
      
      $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
      if (in_array($ext, array('jpg', 'jpeg', 'gif', 'png', 'bmp'))) {
         $newsrc = downloadImage($src, $urlparts['path'], $dir, $prefix);
         $img->src = $newsrc;
      } else {
         echo "\nNot downloading $src\n";
      }
      
      // See if the image is wrapped in a link that is also an image.
      $parent = $img->parent();
      if ($parent->tag == 'a') {
         if (!isset($parent->title) || $parent->title != 'Click to view a larger photo')
            continue;
         
         // Check to see if the image has .Medium.ext'
         $href = $parent->href;
         $hrefparts = parse_url($href);
         if ($hrefparts === false || !isset($hrefparts['path']))
            continue;
         
         $domain = strtolower(stripSubdomain(val('host', $hrefparts)));
         if (!$domain || !in_array($domain, $domains)) {
            continue;
         }

         $ext = strtolower(pathinfo($hrefparts['path'], PATHINFO_EXTENSION));
         if (in_array($ext, array('jpg', 'jpeg', 'gif', 'png', 'bmp'))) {
            $newhref = downloadImage($href, $hrefparts['path'], $dir, $prefix);
            $parent->href = $newhref;
            $parent->class = "PhotoLink";
         } elseif (stripos($src, '.Medium.')) {
            $largesrc = str_ireplace('.Medium.', '.Large.', $src);
            $largesrcparts = parse_url($largesrc);
            $newhref = downloadImage($largesrc, $largesrcparts['path'], $dir, $prefix);
            $parent->href = $newhref;
            $parent->class = "PhotoLink";
         }
      }
   }
   $newstr = $dom->save();
   return $newstr;
}

$lastDownload = microtime(true);

function downloadImage($url, $subpath, $dir, $prefix) {
   global $lastDownload;

   // Make sure not to flood the downloads.
   $sleep = microtime(true) - ($lastDownload + 1);
//   if ($sleep > 0.1)
//      usleep($sleep * 1000000);
   
   $subpath = '/'.ltrim($subpath, '/');
   $path = realpath2($dir.$subpath);
   $newurl = $prefix.$subpath;

   if (!file_exists($path)) {
      ensureDir(dirname($path));
      for ($i = 0; $i < 5; $i++) {
         $r = @copy($url, $path);
         if ($r) {
//            fwrite(STDERR, "\nDownloaded $url.\n");
            
            break;
         } else {
            if (preg_match('`HTTP/1\.\d\s(\d+)`', val(0, $http_response_header), $m)) {
               $code = $m[1];
               if ($code >= 400 && $code <=600) {
                  // This was an error. Just leave it.
                  fwrite(STDERR, "\n$url $code\n");
                  return $url;
               }
            }
         }
      }
      if (!$r) {
         trigger_error("Couldn't download $url after $i tries ($code).", E_USER_WARNING);
      }
   }
   
   $lastDownload = microtime(true);
   return $newurl;
}

function extractBase64Images($str, $dir, $prefix) {
   $cb = function ($match) use ($dir, $prefix) {
      $data = base64_decode(trim($match[2]));
      
      // This file will get a filename coresponding to the data so we don't create duplicate files.
      $filename = sha1($data).'.'.mimeToExt($match[1]);
      
      if (!file_exists($dir)) {
         mkdir($dir, 0777, true);
      }
      $path = "$dir/$filename";
      file_put_contents($path, $data);
      
      return "src=\"$prefix/$filename\"";
   };
   
   $regex = <<<EOT
`src=['"].*?data:(\w+/\w+);base64,([^'"]+)['"]`i
EOT;
   
   $str = preg_replace_callback($regex, $cb, $str);
   
   return $str;
}

/**
 * Take a nested array and flatten it into a one-dimensional array.
 * 
 * @param array $row
 * @return array
 */
function flattenArray($row) {
   if (!isset($result))
      $result = array();
   
   $f = function($row, $path = '') use (&$f, &$result) {
      foreach ($row as $key => $value) {
         if (is_array($value)) {
            if (isset($value[0]))
               $result[$path.$key] = implode(',', $value);
            else
               $f($value, "$key.");
         } else {
            $result[$path.$key] = $value;
         }
      }
   };
   $f($row);
   return $result;
}

function getFullFormats($formats) {
   $result = array();

   foreach ($formats as $table => $options) {
      touchValue('tablename', $options, $table);
      touchValue(TRANSLATE_COLUMNS, $options, array());
      
      // Make sure the columns are all set up in the right form.
      $columns = array();
      foreach ($options[TRANSLATE_COLUMNS] as $key => $value) {
         if (is_int($key)) {
            // Column was specified just as the colum name.
            $key = $value;
            $coldef = array($key);
         } elseif (is_string($value)) {
            // Column was specified to just translate to another name.
            $coldef = array($value);
         } elseif (is_array($value)) {
            $coldef = $value;
         } else {
            trigger_error("Column def for $table.$key is not in the correct format.", E_USER_WARNING);
            $coldef = (array)$value;
         }
         
         touchValue('type', $coldef, 'varchar(255)');
         $coldef['type'] = strtolower($coldef['type']);
         
         $columns[$key] = $coldef;
      }
      $options[TRANSLATE_COLUMNS] = $columns;
      
      $result[$table] = $options;
   }
   return $result;
}

function parseXmlRow($str, $format, $data = array()) {
   $xmli = new SimpleXMLIterator($str);

   $row = xmlIteratorToArray($xmli);
   $row = flattenArray($row);
   
   // Set any data that was passed to this function.
   foreach ($data as $key => $value) {
      $row[$key] = $value;
   }
   
   $row = translateRow($row, $format);
   
//   var_export($row);
   return $row;
}

function removeNoiseWords($slug) {
   global $noisewords;
   $parts = explode('-', $slug);
   $parts = array_filter($parts, function($val) use ($noisewords) {
      return !in_array($val, $noisewords);
   });
   return implode('-', $parts);
}

function stripNamespace($val) {
   $pos = strpos($val, ':');
   if ($pos !== false) {
      return substr($val, $pos + 1);
   }
   return $val;
}

/**
 * 
 * @param type $host
 */
function stripSubdomain($host) {
   $parts = explode('.', $host);
   if (count($parts) <= 2)
      return $host;
   return implode('.', array_splice($parts, -2));
}

define('TRANSLATE_COLUMNS', 'columns');
define('TRANSLATE_ROWFILTER', 'rowfilter');

/**
 * Translate a row with a supplied format.
 * 
 * @param array $row The row to translate.
 * @param array $format The new row with the following format.
 *  - TRANSLATE_COLUMNS: An array in the format oldColumn => array(newColumn, type => 'dbtype' [,'filter' => callable])
 *  - TRANSLATE_ROWFILTER: A callable to be applied to the row before the rest of the translation.
 */
function translateRow(&$row, $format) {
   // Apply the row filter.
   if (isset($format[TRANSLATE_ROWFILTER]) && is_callable($format[TRANSLATE_ROWFILTER])) {
      call_user_func_array($format[TRANSLATE_ROWFILTER], array(&$row));
   }
   
   $result = array();
   foreach ($format[TRANSLATE_COLUMNS] as $key => $opts) {
      if (!array_key_exists($key, $row)) {
         trigger_error("{$format['tablename']}.$key does not exist in the source row.", E_USER_NOTICE);
      } else {
         $value = val($key, $row, null);
      }
      
      // Check to filter the value.
      if (isset($opts['filter'])) {
         $value = call_user_func($opts['filter'], $value, $key, $row);
      }
      
      // Force the column value.
      switch ($opts['type']) {
         case 'tinyint':
         case 'smallint':
         case 'int':
         case 'bigint':
            $value = forceInt($value);
            break;
         case 'datetime':
            if (is_numeric($value))
               $value = gmdate('c', $value);
            else
               $value = gmdate('c', strtotime($value));
            break;
      }
      
      $result[$opts[0]] = $value;
   }
   
   return $result;
}

function percentDot($num, $den) {
   if ($den == 0)
      return '.';
   $percent = $num / $den;
   
   if ($percent < .1)
      return '.';
   if ($percent < .5)
      return '-';
   return '+';
}

/**
 * 
 * @param SimpleXmlIterator $sxi
 * @return type
 */
function xmlIteratorToArray($sxi) {
   $a = array();
   for ($sxi->rewind(); $sxi->valid(); $sxi->next()) {
      if ($sxi->hasChildren())
         $val = xmlIteratorToArray($sxi->current());
      else
         $val = strval($sxi->current());
         
      $key = $sxi->key();
      
      if (array_key_exists($key, $a)) {
         if (is_array($a[$key]) && isset($a[$key][0])) {
            $a[$key][] = $val;
         } else {
            $a[$key] = array(
                  $a[$key],
                  $val
               );
         }
      } else {
         $a[$key] = $val;
      }
   }
   return $a;
}

/**
 * A function similar to realpath, but it doesn't follow symlinks.
 * @param string $path The path to the file.
 * @return string
 */
function realpath2($path) {
   if (substr($path, 0, 2) == '//' || strpos($path, '://'))
      return $path;
   
   $parts = explode('/', str_replace('\\', '/', $path));
   $result = array();
   
   foreach ($parts as $part) {
      if (!$part || $part == '.')
         continue;
      if ($part == '..')
         array_pop($result);
      else
         $result[] = $part;
   }
   $result = '/'.implode('/', $result);
   return $result;
}

main();