#!/usr/bin/php
<?php
/**
 * This script will add image extensions to a folder of files and move them to a destination folder.
 * This is good for stuff like a bunch of vbulletin attachments that end in .attach.
 * 
 * Note: You pust link in https://github.com/vanillaforums/framework for this script to work.
 * 
 */

define('APPLICATION', 'xmldump');

error_reporting(E_ALL & ~E_USER_NOTICE); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);
date_default_timezone_set('America/Montreal');

require_once __DIR__.'/framework/bootstrap.php';
requireFeatures(FEATURE_COMMANDLINE);

function main() {
   $opts = dbOpts(true);
   $opts['source'] = array('The source directory.', CMDLINE_FLAGS => CMDLINE_REQUIRED);
   $opts['dest'] = array('The dest directory.', CMDLINE_FLAGS => CMDLINE_REQUIRED);
   $opts['skip'] = array('A regex string to skip entries.');
   
   $args = parseCommandLine('xmldump', $opts);
   
   $source = $args['source'];
   if (!is_dir($source))
      die("$source is not a directory.\n");
   
   $source = realpath($source);
   $root = substr($source, 0, strrpos($source, '/') + 1);
   
   $db = null;
   if ($args['dbname']) {
      $db = MySqlDb::fromArgs($args);
      $db->px = 'GDN_';
   }
   
   // Iterate over all of the files in the directory.
   addExtensions($source, $args['dest'], $root, $db, $args);
}

/**
 * 
 * @param RecursiveDirectoryIterator $source
 * @param type $dest
 * @param type $root
 */
function addExtensions($source, $dest, $root, $db = null, $options = array()) {
   if (is_string($source))
      $source = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
   
   $skip = val('skip', $options);
   
   for (;$source->valid();$source->next()) {
      $source_path = $source->key();
      if ($skip && preg_match($skip, $source_path)) {
         echo "Skipping $source_path\n";
         continue;
      }
      
      if (is_file($source_path))
         addExtension($source_path, $dest, $root, $db);
      
      if ($source->hasChildren()) {
         addExtensions($source->getChildren(), $dest, $root, $db, $options);
      }
   }
}

/**
 * 
 * @param string $source_path
 * @param string $dest_root
 * @param string $root
 * @param Db $db
 */
function addExtension($source_path, $dest_root, $root, $db = null) {
   // Strip the source root off of the path.
   $name = rtrim(ltrimString($source_path, $root), '/');
   $dest_path = strtolower(rtrim($dest_root, '/').'/'.$name);
//   echo "$dest_path\n";
   
   $ext = '';
   $size = getimagesize($source_path);
   
   if ($size !== false) {
      list($width, $height, $type) = $size;

      // Get the file extension based on the image type.
      switch ($type) {
         case IMAGETYPE_GIF:
            $ext = '.gif';
            break;
         case IMAGETYPE_JPEG:
            $dest_path = preg_replace('`\.jpeg$`', '.jpg', $dest_path);
            $ext = '.jpg';
            break;
         case IMAGETYPE_PNG:
            $ext = '.png';
            break;
      }
   }
   
   // Add the extension to the object.
   $new_name = $name;
   if ($ext && !stringEndsWith($dest_path, $ext)) {
      $dest_path .= $ext;
      $new_name .= $ext;
   }
   
   echo "$dest_path ($new_name)\n";
   
   // Copy the file.
   ensureDir(dirname($dest_path));
   copy($source_path, $dest_path);
   
   // Update the db.
   if (is_a($db, 'Db') && $ext) {
      $rowCount = $db->update('Media',
         array('Path' => $new_name, 'ImageWidth' => $width, 'ImageHeight' => $height),
         array('Path' => $name));
      
      $rowCount = $db->update('Media',
         array('Path' => $new_name, 'ImageWidth' => $width, 'ImageHeight' => $height),
         array('Path' => '~cf/'.$name));
   }
}

main();