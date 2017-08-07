<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * An extension of the ColorPickerPlugin for editing colors.
 */
class ColorPickerSettings {
   const COMMENT_KEY = 'comment';
   const RULES_KEY = 'rules';
   const SELECTOR_KEY = 'selector';

   /// PROPERTIES ///
   protected $_CssFiles = [];

   public $Excluded = ['admin.css', 'deverror.css', 'error.css', 'previewtheme.css', 'setup.css'];

   /** @var ColorPickerPlugin */
   public $Parent;

   /** @var int The number of hues available for editing. */
   public $NumHues = 6.0;

   /// METHODS ///

   protected function addCssFiles($path, $override = FALSE) {
      $this->_AddCssFiles($path, $this->_CssFiles, $override);
   }

   protected function _AddCssFiles($folders, &$result, $override = FALSE) {
      if (is_string($folders) && stringEndsWith($folders, '.css') && file_exists($folders)) {
         // This is a single file, add it to the array.
         $result[basename($folders)] = $folders;
         return;
      }

      $folders = (array)$folders;

      foreach ($folders as $folder) {
         // Grab all of the css files.
         $cssPaths = glob("$folder/*.css");
         if (is_array($cssPaths)) {
            foreach ($cssPaths as $path) {
               $filename = basename($path);
               if (in_array($filename, $this->Excluded))
                  continue;
               $result[] = $path;

               if ($override) {
                  $basename = basename($path);
                  foreach ($result as $index => $resultPath) {
                     if ($basename == basename($resultPath) && $path != $resultPath) {
                        unset($result[$index]);
                     }
                  }
               }
            }
         }

         // Traverse subdirectories.
         $subFolders = glob("$folder/*", GLOB_ONLYDIR);
         if (is_array($subFolders))
            $this->_AddCssFiles($subFolders, $result);
      }
   }

   /** Add the edit colors ui to the page.
    *
    * @param Gdn_Controller $sender
    */
   public function editColors($sender) {
      $appFolder = 'plugins/ColorPicker';

      // Add the css.
      $sender->addCssFile('colorpicker.css', $appFolder);
//      $Sender->addCssFile('layout.css', $AppFolder);
      $sender->addCssFile('colorpicker.plugin.css', $appFolder);

      // Add the js.
      $sender->addJsFile('colorpicker.js', $appFolder);
//      $Sender->addJsFile('eye.js', $AppFolder);
//      $Sender->addJsFile('layout.js', $AppFolder);
//      $Sender->addJsFile('utils.js', $AppFolder);
      $sender->addJsFile('colorpicker.plugin.js', $appFolder);

      // Get all of the data for the view.
      $data = [];
      $path = PATH_UPLOADS."/ColorPicker/custom.css";
      if (!file_exists($path))
         $this->Parent->setup();
      $css = $this->parseCssFile($path);
      $colors = $this->sortCssByColor($css);
      uasort($colors, [$this, 'CompareHSV']);
      $data['Colors'] = $colors;

      // Figure out the average color in the groups.
      $groups = [];
      foreach($colors as $color => $options) {
         list($r, $g, $b) = self::rGB($color);
         $colorGroup = $this->colorGroup($color);
         if (!isset($groups[$colorGroup])) {
            $groups[$colorGroup] = ['R' => $r, 'G' => $g, 'B' => $b, 'Count' => 1];
         } else {
            $groups[$colorGroup]['R'] += $r;
            $groups[$colorGroup]['G'] += $g;
            $groups[$colorGroup]['B'] += $b;
            $groups[$colorGroup]['Count'] += 1;
         }
      }
      foreach ($groups as $index => $values) {
         $r = round($values['R'] / $values['Count']);
         $g = round($values['G'] / $values['Count']);
         $b = round($values['B'] / $values['Count']);
         $groups[$index] = self::rGB2Hex($r, $g, $b);
      }
      $data['Groups'] = $groups;

      $sender->setData('ColorPicker', $data);
      $sender->ColorPicker = $this;

      // Add the view.
      $colorPickerView = $sender->fetchView('ColorPicker', '', $appFolder);
      $sender->addAsset('Content', $colorPickerView, 'ColorPicker');
   }

   /** Filter an array of css rules so that only rules with colors are there.
    * @param array $cssArray An array of css rules returned from parseCssFile().
    * @return array An array in the same format as $cssArray, but only with rules that contain colors.
    */
   public function filterCssColors($cssArray) {
      $result = [];
      foreach($cssArray as $ruleArray) {
         $selector = $ruleArray[self::SELECTOR_KEY];
         $rules = $ruleArray[self::RULES_KEY];
         $filteredRules = [];

         // Loop through the rules looking for colors.
         foreach ($rules as $key => $value) {
            if (preg_match('`(#[0-9a-f]{3,6}).*?(!important)?`i', $value, $matches)) {
               // There is a color in the value. Check to see if the key is supported and transform the rule into a specific color rule.
               if (stringEndsWith($key, 'color')) {
                  // This is a color rule so it can be put in directly.
                  $rule = $key;
               } elseif (stringBeginsWith($key, 'background')) {
                  // Check for gradients.


                  $rule = $key.'-color';
               } elseif (stringBeginsWith($key, 'border')) {
                  $rule = $key.'-color';
               } else {
                  $foo = 'bar';
               }

               if (isset($rule)) {
                  $color = $matches[1];
                  // Convert the color into a standard format.
                  $color = strtolower($color);
                  if (strlen($color) == 4) {
                     $color = "#{$color[1]}{$color[1]}{$color[2]}{$color[2]}{$color[3]}{$color[3]}";
                  }

                  $filteredRules[$rule] = $color.(isset($matches[2]) ? ' '.$matches[2] : '');
               }
            }
         }

         if (count($filteredRules) > 0) {
            $result[] = [
               self::SELECTOR_KEY => $selector,
               self::RULES_KEY => $filteredRules];
         }
      }
      return $result;
   }

   public function formatRuleArray($ruleArray) {
      $result = '';

      if (isset($ruleArray[self::COMMENT_KEY]))
         $result .= "/* {$ruleArray[self::COMMENT_KEY]} */\n";

      if (isset($ruleArray[self::SELECTOR_KEY]) && isset($ruleArray[self::RULES_KEY])) {
         $result = $ruleArray[self::SELECTOR_KEY]." {\n";
         foreach ($ruleArray[self::RULES_KEY] as $key => $value) {
            $result .= " $key: $value;\n";
         }
         $result .= "}\n\n";
      }
      return $result;
   }

   public function generateCustomCss($path) {
      if (!$this->_CssFiles) {
         $cssFiles = $this->getCssFiles();
         $this->_CssFiles = $cssFiles;
      }

      $allColorCss = [];

      // Collect all of the css rules that contain colors.
      foreach ($this->_CssFiles as $cssPath) {
         $css = $this->parseCssFile($cssPath);
         $colorCss = $this->filterCssColors($css);

         if (count($colorCss) > 0) {
            // Only collect the file if there is at least one color rule.
            if (stringBeginsWith($cssPath, PATH_ROOT))
               $virtualPath = substr($cssPath, strlen(PATH_ROOT));
            else
               $virtualPath = $cssPath;
            $allColorCss[] = [self::COMMENT_KEY => $virtualPath];
            $allColorCss = array_merge($allColorCss, $colorCss);
         }
      }

      // Write the rules to the css.
      $fp = fopen($path, 'wb');
      fwrite($fp, '/* File created '.Gdn_Format::toDateTime()." */\n\n");
      foreach ($allColorCss as $css) {
         fwrite($fp, $this->formatRuleArray($css));
      }
      fclose($fp);
   }

   public function getCssFiles() {
      $result = [];

      // Loop through the appropriate folders and grab the paths to the css files in the application.

      // 1. Enabled applications.
      $applicationManager = new Gdn_ApplicationManager();
      $folders = $applicationManager->enabledApplicationFolders();
      foreach ($folders as $index => $folder) {
         $folders[$index] = PATH_APPLICATIONS.'/'.$folder;
      }
      $this->_AddCssFiles($folders, $result);

      // 2. Enabled plugins.
      $pluginManager = Gdn::pluginManager();
      $folders = $pluginManager->enabledPluginFolders();
      foreach ($folders as $index => $folder) {
         if ($folder == 'ColorPicker')
            continue;
         $folders[$index] = PATH_PLUGINS.'/'.$folder;
      }
      $this->_AddCssFiles($folders, $result);

      // 3. Current Theme.
      $themeManager = Gdn::themeManager();
      $currentTheme = $themeManager->enabledThemeInfo();
      if ($currentTheme) {
         $themePath = $currentTheme['ThemeRoot'].'/design';
         $this->_AddCssFiles($themePath, $result, TRUE);
      }

      return $result;
   }

   /** Parse a css file and pick out all of its color selectors.
    *
    * @param string $path The path to the css file.
    * @return array An array in the form:
    *  [0]: [selector: [property: value, property: value,...]]
    *  [1]: [selector: [property: value, property: value,...]]
    *  ...
    */
   public function parseCssFile($path, $stripComments = TRUE) {
      $contents = file_get_contents($path);

      if ($stripComments) {
         $contents = preg_replace('`/\*.*?\*/`s', '', $contents);
      }

      // Grab all of the rules.
      $result = [];
      if (preg_match_all('`([^{]*?){([^}]*?)}`', $contents, $matches, PREG_SET_ORDER)) {
         foreach ($matches as $match) {
            $selectorString = $match[1];
            $rulesString = $match[2];

            // Parse the rules into an array.
            $rulesArray = explode(';', $rulesString);
            $rules = [];
            foreach ($rulesArray as $ruleString) {
               $rule = explode(':', $ruleString, 2);
               if (count($rule) >= 2)
                  $rules[trim($rule[0])] = trim($rule[1]);
            }

            // Add the rule to the result.
            $result[] = [
               self::SELECTOR_KEY => trim($selectorString),
               self::RULES_KEY => $rules];
         }
      }

      return $result;
   }

   public static function rGB($color) {
      if (preg_match('`#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})`i', $color, $matches)) {
         return [hexdec($matches[1]), hexdec($matches[2]), hexdec($matches[3])];
      }
   }

   public static function rGB2Hex($r, $g, $b) {
      $r = dechex($r);
      $g = dechex($g);
      $b = dechex($b);

      if (strlen($r) < 2)
         $r = '0'.$r;
      if (strlen($g) < 2)
         $g = '0'.$g;
      if (strlen($b) < 2)
         $b = '0'.$b;

      return strtolower("#$r$g$b");
   }

   /** Converts a color in RGB to HSV Values.
    *  This function has been adapted from a listing on stackoverflow.com
    *
    * @param int $r The red component of the color in the range [0,255].
    * @param int $g The green component of the color in the range [0,255].
    * @param int $b The blue component of the color in the range [0,255].
    * @return array An array in the form array(H, S, V) where HSV are floats in the range [0,1].
    */
   public static function rGB2HSV ($r, $g = NULL, $b = NULL) {
      if (is_array($r))
         list($r, $g, $b) = $r;
      elseif (is_string($r) && $r[0] == '#')
         list($r, $g, $b) = self::rGB($r);

      $r = $r / 255.0;
      $g = $g / 255.0;
      $b = $b / 255.0;
      $h = 0;
      $s = 0;
      $v = 0;
      $min = min(min($r, $g),$b);
      $max = max(max($r, $g),$b);
      $delta = $max - $min;

      $v = $max;

      if($delta == 0)
      {
         $h = NULL;
         $s = 0;
      }
      else
      {
         $s = $delta / $max;

         $dR = ((($max - $r) / 6) + ($delta / 2)) / $delta;
         $dG = ((($max - $g) / 6) + ($delta / 2)) / $delta;
         $dB = ((($max - $b) / 6) + ($delta / 2)) / $delta;

         if ($r == $max)
            $h = $dB - $dG;
         else if($g == $max)
            $h = (1/3) + $dR - $dB;
         else
            $h = (2/3) + $dG - $dR;

         if ($h < 0)
            $h += 1;
         if ($h > 1)
            $h -= 1;
      }

      return [$h, $s, $v];
   }

   public function colorGroup($h) {
      if (is_string($h) && $h[0] == '#') {
         list($h, $s, $v) = self::rGB2HSV($h);
      }
      if ($h === NULL)
         return NULL;
      return round($h * $this->NumHues) % $this->NumHues;
   }

   /** Sort a css array by color.
    *
    * @param array $cssArray A css array returned from parseCssFile() or filterCssColors().
    * @return array An array in the form:
    * [#000000]: [selector: [property1: '', property2: !important, property3: '',...]]
    * [#ffffff]: [selector: [property1: '', property2: !important, property3: '',...]]
    * ...
    */
   public function sortCssByColor($cssArray) {
      $result = [];

      foreach ($cssArray as $rule) {
         if (!isset($rule[self::RULES_KEY]) || !isset($rule[self::SELECTOR_KEY]))
            continue;

         $selector = $rule[self::SELECTOR_KEY];
         $rules = $rule[self::RULES_KEY];

         foreach ($rules as $key => $value) {
            // Get the color.
            if (preg_match('`(#[0-9a-f]{3,6}).*?(!important)?`i', $value, $matches)) {
               $color = $matches[1];
               $options = getValue(2, $matches, '');
               $result[$color][self::SELECTOR_KEY][$selector][$key] = $options;
               $result[$color]['hsv'] = self::rGB2HSV(self::rGB($color));
            }
         }
      }
      return $result;
   }

   public function compareHSV($a, $b) {
      $hSV_A = $a['hsv'];
      $hSV_B = $b['hsv'];

      $h_A = $hSV_A[0];
      $h_B = $hSV_B[0];

      if ($h_A === NULL && $h_B !== NULL)
         return -1;
      elseif ($h_A !== NULL && $h_B === NULL)
         return 1;
      elseif ($h_A === NULL && $h_B === NULL)
         return self::compare($hSV_A[2], $hSV_B[2]);
      else {
         // Chunk the hues so they are grouped.
         $h_A = $this->colorGroup($h_A);
         $h_B = $this->colorGroup($h_B);

         if ($h_A > $h_B)
            return 1;
         elseif ($h_A < $h_B)
            return -1;
         else {
//            if ($HSV_A[1] == $HSV_B[1])
//               return self::compare($HSV_A[2], $HSV_B[2]);
//            else
//               return ($HSV_A[2] - $HSV_B[2]) / ($HSV_A[1] - $HSV_B[1]);
            return self::compare($hSV_A[1], $hSV_B[1]);
         }
      }
   }

   public static function compare($a, $b) {
      if ($a > $b)
         return 1;
      elseif ($a < $b)
         return -1;
      else
         return 0;
   }
}
