#!/opt/local/bin/php
<?php
error_reporting(E_ALL); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

define('DEBUG', false);

define('API_URL', 'https://vanillaforums.org/api/v1/');
define('API_KEY', 'eaba5fb81dc258ac9dbb2779f3fbe7c1');

//define('API_URL', 'https://vanilla.local');
//define('API_KEY', 'dccc0fe265c23c05cdef2ed207129563');

define('USERNAME', 'Todd');

header('Content-Type: text/plain; charset=utf8');

date_default_timezone_set('America/Montreal');

mysql_connect('127.0.0.1', 'root');
mysql_select_db('_t1');
mysql_query('set names utf8');

$locales = array(
    'en-CA' => array('tx' => 'en_CA', 'name' => 'English'),
    
    'ar-AR' => array('tx' => 'ar', 'name' => 'Arabic', 'flag' => 'Saudi-Arabia-Flag-128.png'),
    'bg-BG' => array('tx' => 'bg_BG', 'name' => 'Bulgarian', 'flag' => 'Bulgaria-Flag-128.png'),
    'bs-BA' => array('tx' => 'bs', 'name' => 'Bosnian', 'flag' => 'Bosnian-Flag-128.png'),
    'da-DK' => array('tx' => 'da', 'name' => 'Danish', 'flag' => 'Denmark64.png'),
    'ca-ES' => array('tx' => 'ca_ES', 'name' => 'Catalan (Spain)', 'flag' => 'Spain-Flag-128.png'),
    'cs-CZ' => array('tx' => 'cs_CZ', 'name' => 'Czech', 'flag' => 'Czech-Republic-Flag-128.png'),
    'de-DE' => array('tx' => 'de_DE', 'name' => 'German', 'flag' => 'Germany-Flag-128.png'),
    'el-GR' => array('tx' => 'el_GR', 'name' => 'Greek', 'flag' => 'Greece-Flag-128.png'),
    'es-ES' => array('tx' => 'es_ES', 'name' => 'Spanish', 'flag' => 'Spain-Flag-128.png'),
    'fa-IR' => array('tx' => 'fa_IR', 'name' => 'Persian (Iran)', 'flag' => 'Iran-Flag-128.png'),
    'fi-FL' => array('tx' => 'fi', 'name' => 'Finnish', 'flag' => 'Finland-Flag-128.png'),
    'fr-FR' => array('tx' => 'fr_FR', 'name' => 'French', 'flag' => 'France-Flag-128.png'),
    'he_IL' => array('tx' => 'he_IL', 'name' => 'Hebrew', 'flag' => 'Israel-Flag-128.png'),
    'hu-HU' => array('tx' => 'hu', 'name' => 'Hungarian', 'flag' => 'Hungary-Flag-128.png'),
    'id-ID' => array('tx' => 'id_ID', 'name' => 'Indonesian', 'flag' => 'Indonesia-Flag-128.png'),
    'it-IT' => array('tx' => 'it', 'name' => 'Italian', 'flag' => 'Italy-Flag-128.png'),
    'ja-JP' => array('tx' => 'ja', 'name' => 'Japanese', 'flag' => 'Japan-Flag-128.png'),
    'lt-LT' => array('tx' => 'lt', 'name' => 'Lithuanian', 'flag' => 'Lithuania-Flag-128.png'),
    'ko-KR' => array('tx' => 'ko_KR', 'name' => 'Korean', 'flag' => 'Korea-Flag-128.png'),
    'my-MM' => array('tx' => 'my_MM', 'name' => 'Burmese (Myanmar)', 'flag' => 'Myanmar-128.png'),
    'nl-NL' => array('tx' => 'nl', 'name' => 'Dutch', 'flag' => 'Netherlands-Flag-128.png'),
    'nb-NO' => array('tx' => 'nb_NO', 'name' => 'Norwegian Bokmål', 'flag' => 'Norway-Flag-128.png'),
    'pl-PL' => array('tx' => 'pl_PL', 'name' => 'Polish', 'flag' => 'Poland-Flag-128.png'),
    'pt-BR' => array('tx' => 'pt_BR', 'name' => 'Portuguese (Brazil)', 'flag' => 'Portugal-Flag-128.png'),
    'ro-RO' => array('tx' => 'ro_RO', 'name' => 'Romanian', 'flag' => 'Romania-Flag-128.png'),
    'ru-RU' => array('tx' => 'ru_RU', 'name' => 'Russian', 'flag' => 'Russia-Flag-128.png'),
    'sv-SE' => array('tx' => 'sv_SE', 'name' => 'Swedish', 'flag' => 'Sweden-Flag-128.png'),
    'th_TH' => array('tx' => 'th_TH', 'name' => 'Thai', 'flag' => 'Thailand-Flag-128.png'),
    'tr-TR' => array('tx' => 'tr_TR', 'name' => 'Turkish', 'flag' => 'Turkey-Flag-128.png'),
    'uk-UA' => array('tx' => 'uk', 'name' => 'Ukranian', 'flag' => 'Ukraine-Flag-128.png'),
    'ur-PL' => array('tx' => 'ur_PK', 'name' => 'Urdu (Pakistan)', 'flag' => 'Pakistan-Flag-128.png'),
    'vi_VN' => array('tx' => 'vi_VN', 'name' => 'Vietnamese', 'flag' => 'Vietnam-Flag-128.png'),
    'zh-CN' => array('tx' => 'zh_CN', 'name' => 'Chinese (China)', 'flag' => 'China-Flag-128.png'),
    'zh-TW' => array('tx' => 'zh_TW', 'name' => 'Chinese (Taiwan)', 'flag' => 'Taiwan-Flag-128.png')
    );

$enDefs = array();

function main($argv, $argc) {
   global $locales;
   
   $pull = in_array('nopull', $argv) ? false : true;
   if ($pull) {
      // Pull files from the transifex folder and copy the locales here.
      chdir('/www/tx/vanilla');
      passthru("tx pull -f");
   }
   
   foreach ($locales as $code => $info) {
      if ($code == 'en-CA') {
         generateFilesFromDb($code);
         copyLocaleToTx($code);
         
         continue;
      }
      
      echo "\n$code:\n";
      
      // Save the definitions from transifex.
      saveLocaleToDb($code);
      
      // Generate the file in locales.
      $changed = generateFilesFromDb($code);
      
      if (in_array('push', $argv)) {
         // Copy the file back to transifex.
         copyLocaleToTx($code);

         // Push the file back to transifex.com
         chdir('/www/tx/vanilla');
         passthru("tx push -t -l {$info['tx']}");
      }
      
      // Upload the file to the addons site.
      if (in_array('upload', $argv))
         uploadLocale($code);
   }
   
   
//   if ($copy) {
//      // Copy all of the locales.
//      foreach ($locales as $code => $info) {
//         echo "Copying $code...";
//         copyLocaleToAddons($code);
//         echo "done.\n";
//      }
//   }
//   
//   if ($save) {
//      // Save the translations to the database.
//      saveLocales();
//      
//      // Re-generate the definitions from the database.
//      $generateFilesFromDb();
//   }
//   
//   
//   
//   // Copy the locales back to transifex.
//   if (in_array('push', $argv)) {
//      foreach ($locales as $code => $info) {
//         echo "Copying $code back to transifex folder...";
//         copyLocaleToTx($code);
//         echo "done.\n";
//      }
//   }
//      
//   // Push the files the addons site.
//   if (in_array('upload', $argv)) {
//      uploadLocales();
//   }
}

//function copyLocaleToAddons($code) {
//   global $locales;
//   
//   $info = $locales[$code];
//   
//   $tx = $info['tx'];
//   
//   $slug = 'vf_'.str_replace('-', '_', $code);
//   $folder = "/www/locales/$slug";
//   
//   // Make sure the folder for the locale exists.
//   if (!file_exists($folder))
//      mkdir($folder, 0777, TRUE);
//   
//   
//   
//   // Copy the transifex definitions.
//   $changed = false;
//   $resources = array('site_core', 'dash_core', 'archive_core');
//   $txFolder = ($tx == 'en_CA' ? 'source' : 'translations');
//   foreach ($resources as $resource) {
//      $source = "/www/tx/vanilla/$txFolder/vanilla.$resource/$tx.php";
//      $dest = "$folder/$resource.php";
//      
//      if (file_exists($source)) {
//         formatDefs($source, $dest.'.copied');
//         
//         if (!file_exists($dest))
//            $changed = true;
//         elseif (!$changed)
//            $changed = (md5_file($dest) != md5_file($dest.'.copied'));
//         
//         rename($dest.'.copied', $dest);
//      }
//   }
//   
//   $infoPath = "$folder/definitions.php";
//   
//   if ($changed || !file_exists($infoPath)) {
//      $version = gmdate('Y.m.d');
//      
//      // Create the info array.
//      $infoArray = array(
//          'Locale' => $code,
//          'Name' => $info['name'].' Transifex',
//          'Description' => "{$info['name']} language translations for Vanilla. Help contribute to this translation by going to its translation site <a href=\"https://www.transifex.com/projects/p/vanilla/language/$tx/\">here</a>.",
//          'Version' => $version,
//          'Author' => 'Vanilla Community',
//          'AuthorUrl' => "https://www.transifex.com/projects/p/vanilla/language/$tx/"
//      );
//
//      $infoString = "<?php\n\n \$LocaleInfo['{$slug}'] = ".var_export($infoArray, TRUE).";\n";
//      file_put_contents($infoPath, $infoString);
//   } else {
//      echo '(not changed) ';
//   }
//}

function copyLocaleToTx($locale) {
   global $locales;
   global $enDefs;
   
   $info = $locales[$locale];
   $tx = $info['tx'];
   $slug = str_replace('-', '_', $locale);
   
   $resources = array('site', 'dash', 'archive');
   $txFolder = ($tx == 'en_CA' ? 'source' : 'translations');
   
   foreach ($resources as $resource) {
      $source = dirname(__FILE__)."/vf_{$slug}/{$resource}_core.php";
      $dest = "/www/tx/vanilla/$txFolder/vanilla.{$resource}_core/{$tx}.php";
      
      if (!file_exists($source))
         continue;
      
      if (!file_exists(dirname($dest)))
            mkdir(dirname($dest), 0777, true);
      
      // We need to reload the definitions and remove the missing ones so they can be translated again.
//      $Definition = array();
//      include $source;
//      
//      if (isset($enDefs[$resource])) {
//         $en = $enDefs[$resource];
//         foreach ($en as $k => $v) {
//            if (!array_key_exists($k, $Definition))
//               $Definition[$k] = '';
//         }
//      }
//      saveDefs($Definition, $dest);      
      
      copy($source, $dest);
   }
}

function formatDefs($source, $dest) {
   $Definition = array();
   require $source;
   
   // Clear out all of the blank definitions.
   $Definition = removeBadTranslations($Definition);
   
   saveDefs($Definition, $dest);
}

function removeBadTranslations($arr, $removeEnglish = false) {
   static $enDefs = null;
   
   if ($enDefs === null) {
      $enDefs = loadTranslationsFromDb('en-CA', null);
   }
   
   $result = array();
   
   foreach ($arr as $k => $v) {
      if (!$v)
         continue;
      if ($k == $v)
         continue;
      if (strpos($v, '???') !== false)
         continue;
      if ($removeEnglish && isset($enDefs[$k]) && $enDefs[$k] == $v)
         continue;
          
      $result[$k] = $v;
   }
   return $result;
}

function strnatcasecmp2($a, $b) {
   $la = strtolower($a);
   $lb = strtolower($b);
   
   $c = strnatcmp($la, $lb);
   if ($c !== 0)
      return $c;
   
   return strnatcmp($a, $b);
   
//   for ($i = 0; $i < strlen($a); $i++) {
//      $ca = $a[$i];
//      $cb = $
//   }
}

function saveDefs(&$defs, $path) {
   uksort($defs, 'strnatcasecmp2');
   
   // Backup the current file to check for changes.
   if (file_exists($path)) {
      $bakPath = $path.'.bak';
      copy($path, $bakPath);
   }
   
   if (!file_exists(dirname($path))) {
      mkdir(dirname($path), 0777, true);
   }
   
   $fp = fopen($path, 'wb');
   
   fwrite($fp, "<?php\n");
   
   $last = '';
   
   foreach ($defs as $Key => $Value) {
      $curr = strtolower(substr($Key, 0, 1));
      
      if ($curr !== $last)
         fwrite($fp, "\n");
      
      fwrite($fp, '$Definition['.var_export($Key, TRUE).'] = '.var_export($Value, TRUE).";\n");
      
      $last = $curr;
   }
   fclose($fp);
   
   // Compare the backup to the current file.
   if (isset($bakPath)) {
      $md51 = md5_file($path);
      $md52 = md5_file($bakPath);
      $changed = $md51 != $md52;
      unlink($bakPath);
   } else {
      $changed = true;
   }
   return $changed;
}

function generateFilesFromDb($locale) {
   $files = array('site', 'dash', 'archive');
   $changed = false;
   
   $slug = str_replace('-', '_', $locale);
   
   $missing = array();

   echo "Generating $locale from db.\n";
   
   foreach ($files as $file) {
      $path = getcwd()."/vf_{$slug}/{$file}_core.php";
      
      echo "  $path";

      $r = generateFileFromDb($locale, $path, $file, $missing);

      if (!$r)
         echo " (not changed)";
      else 
         $changed = true;
      
      echo "\n";
   }
   
   @unlink(dirname(__FILE__)."/vf_{$slug}/bad_defs.php");
   
   // Write the missing arrays.
   echo 'missing ';
   $missingPath = dirname(__FILE__)."/vf_{$slug}/missing.php";
   $r = saveDefs($missing, $missingPath);
   if (!$r)
      echo '(not changed) ';
   else
      $changed = true;
   
   if ($changed) {
      // Also write the info array.
      echo 'info ';
      writeInfoArray($locale);
   }

   echo "done.\n";
   
   return $changed;
}

function loadTranslationsFromDb($locale, $type) {
   switch ($type) {
      case 'dash':
         $Dashboard = 1;
         $Active = 1;
         break;
      case 'site':
         $Dashboard = 0;
         $Active = 1;
         break;
      case 'archive':
         $Dashboard = null;
         $Active = 2;
         break;
      default:
         $Dashboard = null;
         $Active = null;
   }
   
   // Load the stuff from the db.
   if ($locale === 'en-CA') {
      $sql = "select
         c.Name,
         coalesce(t.Translation, c.Name) as Translation
      from GDN_LocaleCode c
      left join GDN_LocaleTranslation t
         on c.CodeID = t.CodeID and t.Locale = :Locale";
   } else {
      $sql = "select
         c.Name,
         t.Translation
      from GDN_LocaleCode c
      join GDN_LocaleTranslation t
         on c.CodeID = t.CodeID and t.Locale = :Locale";
   }
   
   $where = array();
   
   if ($Active !== null)
      $where[] = 'c.Active = :Active';
   
   if ($Dashboard !== null)
      $where[] = 'c.Dashboard = :Dashboard';
   
   if (!empty($where))
      $sql .= "\nwhere ".implode("\n  and ", $where);
   
   $r = query($sql, array('Locale' => $locale, 'Dashboard' => $Dashboard, 'Active' => $Active));
   $defs = array();
   while ($row = mysql_fetch_assoc($r)) {
      if (!$row['Name'])
         continue;
      
      $name = $row['Name'];
      
      $translation = $row['Translation'];
      if ($locale == 'en-CA') {
         if (!$translation)
            $translation = $name;
      } else {
         if (!$translation)
            continue;
         if ($translation == $name)
            continue;
         
         if (strpos($translation, '???') !== false)
            continue;
      }
      
      $defs[$name] = $row['Translation'];
   }
   
   return $defs;
}

function generateFileFromDb($locale, $path, $type, &$missing) {
   global $enDefs;
   if (!isset($enDefs[$type])) {
      $enDefs[$type] = loadTranslationsFromDb('en-CA', $type);
   }
   
   $defs = loadTranslationsFromDb($locale, $type);
   
   if ($type != 'archive') {
      $myMissing = array_diff_key($enDefs[$type], $defs);
      $missing = array_merge($missing, $myMissing);
   }
   
   // Save the definitions to the file.
   $changed = saveDefs($defs, $path);
   return $changed;
}

function getValue($key, $array, $default = null) {
   return isset($array[$key]) ? $array[$key] : $default;
}

//function notEmpty($str) {
//   return !empty($str);
//}

function query($sql, $params = array()) {
   foreach ($params as $key => $value) {
      $sql = str_replace(':'.$key, "'".mysql_real_escape_string($value)."'", $sql);
   }
   
//   echo "$sql\n";
   
   $r = mysql_query($sql);
   if (!$r) {
      trigger_error("Error in:\n$sql\n\n".mysql_error(), E_USER_ERROR);
      return $r;
   }
   
   if (preg_match('`^\s*insert\s`', $sql))
      $r = mysql_insert_id();
   
   return $r;
}

function dbDate($timestamp = null) {
   if (!$timestamp)
      $timestamp = time();
   
   return gmdate('Y-m-d G:i:s', $timestamp);
}

//function saveLocales() {
//   global $locales;
//   
//   // Copy all of the locales.
//   foreach ($locales as $code => $info) {
//      if ($code == 'en-CA')
//         continue;
//      
//      echo "Saving $code...";
//      
//      $result = saveLocaleToDb($code);
//      
//      echo "inserted: {$result['inserted']}, updated: {$result['updated']}, equal: {$result['equal']}, skipped: {$result['skipped']}.\n";
//   }
//}

function saveLocaleToDb($locale, $removeEnglish = true) {
   echo "Saving $locale to db...";
   
   global $locales;
   $info = $locales[$locale];
   $tx = $info['tx'];
   
   $result = array('inserted' => 0, 'updated' => 0, 'equal' => 0, 'skipped' => 0);
   $codes = array('dash_core', 'site_core', 'archive_core');
   
   // First load all of the strings from the db.
   $sql = "select
      c.CodeID, 
      c.Name,
      t.TranslationID,
      t.Locale,
      t.Translation
   from GDN_LocaleCode c
   left join GDN_LocaleTranslation t
      on c.CodeID = t.CodeID and t.Locale = :Locale";
   
   $strings = array();
   $r = query($sql, array('Locale' => $locale));
   while ($row = mysql_fetch_assoc($r)) {
      $strings[$row['Name']] = $row;
   }
   
   // Load the strings from the files.
   $slug = str_replace('-', '_', $locale);
   foreach ($codes as $code) {
      $path = "/www/tx/vanilla/translations/vanilla.$code/$tx.php";
      if (!file_exists($path)) {
         echo "Path $path does not exist.\n";
         continue;
      }
      
      $Definition = array();
      include $path;
      $Definition = removeBadTranslations($Definition, $removeEnglish);
      
      foreach ($Definition as $code => $string) {
         // Make sure the code is even in the db.
         if (!isset($strings[$code])) {
            $result['skipped']++;
            continue;
         }
         
         $row =& $strings[$code];
         
         // Check to see if the string has changed.
         if ($row['Translation'] == $string) {
            $result['equal']++;
            continue;
         }
         
         $now = dbDate();
         
         if ($row['TranslationID']) {
            $r = query("update GDN_LocaleTranslation
               set Translation = :Translation,
                  DateUpdated = :DateUpdated
               where TranslationID = :TranslationID",
               array('TranslationID' => $row['TranslationID'], 'Translation' => $string, 'DateUpdated' => $now));
            if ($r)
               $result['updated']++;
         } else {
            $translationID = query('insert GDN_LocaleTranslation (
                  CodeID,
                  Locale,
                  Translation,
                  DateInserted,
                  InsertUserID)
               values (
                  :CodeID,
                  :Locale,
                  :Translation,
                  :DateInserted,
                  :InsertUserID)',
               array (
                   'CodeID' => $row['CodeID'],
                   'Locale' => $locale,
                   'Translation' => $string,
                   'DateInserted' => $now,
                   'InsertUserID' => 1));
            if ($r) {
               $result['inserted']++;
               $row['TranslationID'] = $r;
               $row['Translation'] = $string;
            }
         }
         
         $row['Translation'] = $string;
      }
   }
   
   echo "inserted: {$result['inserted']}, updated: {$result['updated']}, equal: {$result['equal']}, skipped: {$result['skipped']}.\n";
   
   return $result;
}

/**
 * Make a vanilla api call.
 * @param type $path
 * @param type $post
 * @return type
 */
function api($path, $post = false) {
   // Build the url.
   $url = rtrim(API_URL, '/').'/'.ltrim($path, '/');
   if (strpos($url, '?') === false)
      $url .= '?';
   else
      $url .= '&';
   $url .= 'access_token='.urlencode(API_KEY);
   
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_HEADER, false);
   curl_setopt($ch, CURLOPT_VERBOSE, DEBUG);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
   curl_setopt($ch, CURLOPT_URL, $url);
   
   if ($post !== false) {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
      echo "  POST $url";
   } else {
      echo "  GET  $url";
   }
   
   $response = curl_exec($ch);
   
   $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
   curl_close($ch);
   
   echo " $httpCode\n";
   
   if (strpos($contentType, 'json') !== FALSE)
      $result = json_decode($response, TRUE);
   else
      $result = $response;
   
   if (is_array($result) && isset($result['Exception']))
      echo "  {$result['Exception']}\n";
   
   return $result;
}

function uploadLocale($locale) {
   global $locales;
   $info = $locales[$locale];
   
   // Copy all of the locales.
   if ($locale == 'en-CA')
      return;

   $slug = str_replace('-', '_', $locale);

   $path = dirname(__FILE__)."/vf_$slug";
   
   // Load the info.
   $LocaleInfo = array();
   include "$path/definitions.php";
   $myAddon = $LocaleInfo['vf_'.$slug];

   // Create a zip of the locale.
   $zipPath = zipFolder($path);

   echo "Uploading $locale...\n";

   // Check to see if the addon exists.
   $addon = api("/addon/vf_$slug-locale.json");
   if (isset($addon['Exception'])) {
      if ($addon['Code'] == 404) {
         // Upload the new addon.
         $post = array(
             'File' => '@'.$zipPath
             );
         $r = api('/addon/add.json', $post);
         if (is_array($r)) {
            $addon = api("/addon/vf_$slug-locale.json");
            $addonID = $addon['AddonID'];
         }

      } else {
         echo $addon['Exception']."\n";
         return;
      }
   } else {
      $addonID = $addon['AddonID'];
      
      if ($myAddon['Version'] != $addon['Version']) {
         // Upload a new version of the addon.
         $post = array(
            'File' => '@'.$zipPath
            );
         $r = api('/addon/newversion.json?addonid='.urlencode($addonID), $post);
      } else {
         if ($addon['Icon'])
            unset($info['flag']);
      }
   }

   if (DEBUG && isset($r)) {
      var_export($r);
      echo "\n";
   }

   // Upload the icon.
   if (isset($info['flag'])) {
      $flagPath = "/www/misc/media/flags/{$info['flag']}";

      if (file_exists($flagPath)) {
         $post = array(
             'Icon' => '@'.$flagPath
             );
         $r = api("/addon/icon.json?addonid=$addonID", $post);
      } else {
         echo "  File $flagPath doesn't exist.\n";
      }
   }

   // Possibly change the name.
   if ($addon['Name'] != USERNAME) {
      api("/addon/changeowner.json?addonid=$addonID", array('User' => USERNAME));
   }

   echo "Done.\n";
}

function writeInfoArray($locale) {
   global $locales;
   $info = $locales[$locale];
   $tx = $info['tx'];
   $slug = 'vf_'.str_replace('-', '_', $locale);
   $infoPath = dirname(__FILE__)."/$slug/definitions.php";
   
   $version = date('Y.m.dpHi');
      
   // Create the info array.
   $infoArray = array(
       'Locale' => $locale,
       'Name' => $info['name'].' Transifex',
       'Description' => "{$info['name']} language translations for Vanilla. Help contribute to this translation by going to its translation site <a href=\"https://www.transifex.com/projects/p/vanilla/language/$tx/\">here</a>.",
       'Version' => $version,
       'Author' => 'Vanilla Community',
       'AuthorUrl' => "https://www.transifex.com/projects/p/vanilla/language/$tx/"
   );

   $infoString = "<?php\n\n \$LocaleInfo['{$slug}'] = ".var_export($infoArray, TRUE).";\n";
   file_put_contents($infoPath, $infoString);
}

function zipFolder($path) {
   $path = rtrim($path, '/');
   $filename = trim(strrchr($path, '/'), '/');
   
   $folder = substr($path, 0, -(strlen($filename) + 1));
   
   $destPath = "$folder/zips/{$filename}.zip";
   
   // Grab all of the php files out of the folder.
   $files = glob("$path/*.php");
   
   echo "Creating $destPath...";
   
   $zip = new ZipArchive();
   if($zip->open($destPath, ZIPARCHIVE::OVERWRITE) !== true) {
     echo "error opening zip.\n";
     return false;
   }
   
   foreach($files as $file) {
      $zip->addFile($file, $filename.'/'.basename($file));
   }
   
   echo "done.\n";
   return $destPath;
}

main($argv, $argc);