<?php

/// Hey Peeps!, Make sure functions.commandline.php is symlinked into this folder.

error_reporting(E_ALL); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

require_once dirname(__FILE__) . '/functions.commandline.php';

function main() {
   $opts = array(
   );
   $files = array('inputpath');

   $options = parseCommandLine($opts, $files);
   $path = $options['inputpath'];
   if (!file_exists($path)) {
      echo "File does not exist: $path\n";
      die();
   }

   $formats = array(
      'Discussion' => array(
         'columns' => array(
            'DiscussionKey.KeyWithoutForumKey' => array('ForeignID', 'type' => 'varchar(32)', 'filter' => 'stripNamespace'),
            'CategoryID' => array('CategoryID', 'type' => 'int'), // from row filter
            'CategoryName' => array('Category.Name'),
            'CategoryKey.Key' => array('Category.ParentKey'),
            'Body' => array('Body', 'type' => 'text'),
            'Format' => array('Format', 'type' => 'varchar(10)'),
            'Title' => array('Name'),
            'IsSticky' => array('Announce', 'type' => 'tinyint'),
            'IsClosed' => array('Closed', 'type' => 'tinyint'),
            'RowType' => array('Type', 'type' => 'varchar(255)'),
            'Owner.Key' => array('InsertUserKey'),
            'CreatedOn' => array('DateInserted', 'type' => 'datetime'),
            'SiteOfOriginKey' => array('Site', 'filter' => 'stripSubdomain')
            ),
         'rowfilter' => function(&$row) {
            // Parse the forum info.
            $forumKey = val('ForumKey.Key', $row, '');
            $parts = explode(':', $forumKey);
            $row['CategoryID'] = val(2, $parts);
            $row['CategoryName'] = html_entity_decode(val(1, $parts));
            
            $row['Format'] = 'Html';
         }),
      'Post' => array(
         
         )
   );

   dumpXmlFile($path, $formats);
}

function dumpXmlFile($path, $formats) {
   $formats = getFullFormats($formats);
   $counts = array_fill_keys(array_keys($formats), 0);
   $names = array();

   $xml = new XmlReader();
   $xml->open($path);
   
   while ($xml->read()) {
      if ($xml->nodeType != XMLReader::ELEMENT)
         continue;

      $name = $xml->name;

      if (!isset($names[$name])) {
         $names[$name] = TRUE;
      }

      if (isset($formats[$name])) {
         $str = $xml->readOuterXml();
         parseXmlRow($str, $formats[$name]);
         $xml->next();
         die();
      }

//      switch ($name) {
//         case 'wp:author':
//            $Str = $xml->readOuterXml();
//            $xml->next();
//            break;
//         case 'wp:category':
//            $Str = $xml->readOuterXml();
//            $xml->next();
//            break;
//         case 'item':
////               $Dom = $Xml->expand();
////               $Str = $Xml->readString();
//
//            $Str = $xml->readOuterXml();
////               if ($Str) {
//               $this->ParseDiscussion($Str);
//               $xml->next();
////               }
//            break;
////            case 'wp:comment':
////               $Str = $Xml->readOuterXml();
////               $this->ParseComment($Str);
////               $Xml->next();
////               
////               $Counts['Comments']++;
////               
////               
////               break;
//      }
   }
   
   ksort($names);
   
   var_dump($names);
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

/**
 * Force a value to be an integer.
 * 
 * @param type $value
 */
function forceInt($value) {
   if (is_string($value)) {
      switch (strtolower($value)) {
         case 'false':
         case 'no':
         case '':
            return 0;
      }
      return 1;
   }
   return intval($value);
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

function parseXmlRow($str, $format) {
   $xmli = new SimpleXMLIterator($str);

   $row = xmlIteratorToArray($xmli);
   $row = flattenArray($row);
//   $row = translateRow($row, $format);
   var_export($row);
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
      $value = val($key, $row, null);
      
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
            break;
      }
      
      $result[$opts[0]] = $value;
   }
   
   return $result;
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

function touchValue($key, &$array, $default) {
   if (!array_key_exists($key, $array))
      $array[$key] = $default;
}

main();