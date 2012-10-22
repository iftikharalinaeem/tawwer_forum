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
   protected $_CssFiles = array();

   public $Excluded = array('admin.css', 'deverror.css', 'error.css', 'previewtheme.css', 'setup.css');

   /** @var ColorPickerPlugin */
   public $Parent;

   /** @var int The number of hues available for editing. */
   public $NumHues = 6.0;

   /// METHODS ///

   protected function AddCssFiles($Path, $Override = FALSE) {
      $this->_AddCssFiles($Path, $this->_CssFiles, $Override);
   }

   protected function _AddCssFiles($Folders, &$Result, $Override = FALSE) {
      if (is_string($Folders) && StringEndsWith($Folders, '.css') && file_exists($Folders)) {
         // This is a single file, add it to the array.
         $Result[basename($Folders)] = $Folders;
         return;
      }

      $Folders = (array)$Folders;

      foreach ($Folders as $Folder) {
         // Grab all of the css files.
         $CssPaths = glob("$Folder/*.css");
         if (is_array($CssPaths)) {
            foreach ($CssPaths as $Path) {
               $Filename = basename($Path);
               if (in_array($Filename, $this->Excluded))
                  continue;
               $Result[] = $Path;
               
               if ($Override) {
                  $Basename = basename($Path);
                  foreach ($Result as $Index => $ResultPath) {
                     if ($Basename == basename($ResultPath) && $Path != $ResultPath) {
                        unset($Result[$Index]);
                     }
                  }
               }
            }
         }

         // Traverse subdirectories.
         $SubFolders = glob("$Folder/*", GLOB_ONLYDIR);
         if (is_array($SubFolders))
            $this->_AddCssFiles($SubFolders, $Result);
      }
   }

   /** Add the edit colors ui to the page.
    *
    * @param Gdn_Controller $Sender
    */
   public function EditColors($Sender) {
      $AppFolder = 'plugins/ColorPicker';

      // Add the css.
      $Sender->AddCssFile('colorpicker.css', $AppFolder);
//      $Sender->AddCssFile('layout.css', $AppFolder);
      $Sender->AddCssFile('colorpicker.plugin.css', $AppFolder);
      
      // Add the js.
      $Sender->AddJsFile('colorpicker.js', $AppFolder);
//      $Sender->AddJsFile('eye.js', $AppFolder);
//      $Sender->AddJsFile('layout.js', $AppFolder);
//      $Sender->AddJsFile('utils.js', $AppFolder);
      $Sender->AddJsFile('colorpicker.plugin.js', $AppFolder);

      // Get all of the data for the view.
      $Data = array();
      $Path = PATH_UPLOADS."/ColorPicker/custom.css";
      if (!file_exists($Path))
         $this->Parent->Setup();
      $Css = $this->ParseCssFile($Path);
      $Colors = $this->SortCssByColor($Css);
      uasort($Colors, array($this, 'CompareHSV'));
      $Data['Colors'] = $Colors;

      // Figure out the average color in the groups.
      $Groups = array();
      foreach($Colors as $Color => $Options) {
         list($R, $G, $B) = self::RGB($Color);
         $ColorGroup = $this->ColorGroup($Color);
         if (!isset($Groups[$ColorGroup])) {
            $Groups[$ColorGroup] = array('R' => $R, 'G' => $G, 'B' => $B, 'Count' => 1);
         } else {
            $Groups[$ColorGroup]['R'] += $R;
            $Groups[$ColorGroup]['G'] += $G;
            $Groups[$ColorGroup]['B'] += $B;
            $Groups[$ColorGroup]['Count'] += 1;
         }
      }
      foreach ($Groups as $Index => $Values) {
         $R = round($Values['R'] / $Values['Count']);
         $G = round($Values['G'] / $Values['Count']);
         $B = round($Values['B'] / $Values['Count']);
         $Groups[$Index] = self::RGB2Hex($R, $G, $B);
      }
      $Data['Groups'] = $Groups;

      $Sender->SetData('ColorPicker', $Data);
      $Sender->ColorPicker = $this;
      
      // Add the view.
      $ColorPickerView = $Sender->FetchView('ColorPicker', '', $AppFolder);
      $Sender->AddAsset('Content', $ColorPickerView, 'ColorPicker');
   }

   /** Filter an array of css rules so that only rules with colors are there.
    * @param array $CssArray An array of css rules returned from ParseCssFile().
    * @return array An array in the same format as $CssArray, but only with rules that contain colors.
    */
   public function FilterCssColors($CssArray) {
      $Result = array();
      foreach($CssArray as $RuleArray) {
         $Selector = $RuleArray[self::SELECTOR_KEY];
         $Rules = $RuleArray[self::RULES_KEY];
         $FilteredRules = array();

         // Loop through the rules looking for colors.
         foreach ($Rules as $Key => $Value) {
            if (preg_match('`(#[0-9a-f]{3,6}).*?(!important)?`i', $Value, $Matches)) {
               // There is a color in the value. Check to see if the key is supported and transform the rule into a specific color rule.
               if (StringEndsWith($Key, 'color')) {
                  // This is a color rule so it can be put in directly.
                  $Rule = $Key;
               } elseif (StringBeginsWith($Key, 'background')) {
                  // Check for gradients.
                  
                  
                  $Rule = $Key.'-color';
               } elseif (StringBeginsWith($Key, 'border')) {
                  $Rule = $Key.'-color';
               } else {
                  $Foo = 'bar';
               }

               if (isset($Rule)) {
                  $Color = $Matches[1];
                  // Convert the color into a standard format.
                  $Color = strtolower($Color);
                  if (strlen($Color) == 4) {
                     $Color = "#{$Color[1]}{$Color[1]}{$Color[2]}{$Color[2]}{$Color[3]}{$Color[3]}";
                  }

                  $FilteredRules[$Rule] = $Color.(isset($Matches[2]) ? ' '.$Matches[2] : '');
               }
            }
         }

         if (count($FilteredRules) > 0) {
            $Result[] = array(
               self::SELECTOR_KEY => $Selector,
               self::RULES_KEY => $FilteredRules);
         }
      }
      return $Result;
   }

   public function FormatRuleArray($RuleArray) {
      $Result = '';

      if (isset($RuleArray[self::COMMENT_KEY]))
         $Result .= "/* {$RuleArray[self::COMMENT_KEY]} */\n";

      if (isset($RuleArray[self::SELECTOR_KEY]) && isset($RuleArray[self::RULES_KEY])) {
         $Result = $RuleArray[self::SELECTOR_KEY]." {\n";
         foreach ($RuleArray[self::RULES_KEY] as $Key => $Value) {
            $Result .= " $Key: $Value;\n";
         }
         $Result .= "}\n\n";
      }
      return $Result;
   }

   public function GenerateCustomCss($Path) {
      if (!$this->_CssFiles) {
         $CssFiles = $this->GetCssFiles();
         $this->_CssFiles = $CssFiles;
      }

      $AllColorCss = array();

      // Collect all of the css rules that contain colors.
      foreach ($this->_CssFiles as $CssPath) {
         $Css = $this->ParseCssFile($CssPath);
         $ColorCss = $this->FilterCssColors($Css);

         if (count($ColorCss) > 0) {
            // Only collect the file if there is at least one color rule.
            if (StringBeginsWith($CssPath, PATH_ROOT))
               $VirtualPath = substr($CssPath, strlen(PATH_ROOT));
            else
               $VirtualPath = $CssPath;
            $AllColorCss[] = array(self::COMMENT_KEY => $VirtualPath);
            $AllColorCss = array_merge($AllColorCss, $ColorCss);
         }
      }

      // Write the rules to the css.
      $fp = fopen($Path, 'wb');
      fwrite($fp, '/* File created '.Gdn_Format::ToDateTime()." */\n\n");
      foreach ($AllColorCss as $Css) {
         fwrite($fp, $this->FormatRuleArray($Css));
      }
      fclose($fp);
   }

   public function GetCssFiles() {
      $Result = array();

      // Loop through the appropriate folders and grab the paths to the css files in the application.

      // 1. Enabled applications.
      $ApplicationManager = new Gdn_ApplicationManager();
      $Folders = $ApplicationManager->EnabledApplicationFolders();
      foreach ($Folders as $Index => $Folder) {
         $Folders[$Index] = PATH_APPLICATIONS.'/'.$Folder;
      }
      $this->_AddCssFiles($Folders, $Result);

      // 2. Enabled plugins.
      $PluginManager = Gdn::PluginManager();
      $Folders = $PluginManager->EnabledPluginFolders();
      foreach ($Folders as $Index => $Folder) {
         if ($Folder == 'ColorPicker')
            continue;
         $Folders[$Index] = PATH_PLUGINS.'/'.$Folder;
      }
      $this->_AddCssFiles($Folders, $Result);

      // 3. Current Theme.
      $ThemeManager = new Gdn_ThemeManager();
      $CurrentTheme = $ThemeManager->EnabledThemeInfo();
      if ($CurrentTheme) {
         $ThemePath = $CurrentTheme['ThemeRoot'].'/design';
         $this->_AddCssFiles($ThemePath, $Result, TRUE);
      }

      return $Result;
   }

   /** Parse a css file and pick out all of its color selectors.
    *
    * @param string $Path The path to the css file.
    * @return array An array in the form:
    *  [0]: [selector: [property: value, property: value,...]]
    *  [1]: [selector: [property: value, property: value,...]]
    *  ...
    */
   public function ParseCssFile($Path, $StripComments = TRUE) {
      $Contents = file_get_contents($Path);

      if ($StripComments) {
         $Contents = preg_replace('`/\*.*?\*/`s', '', $Contents);
      }

      // Grab all of the rules.
      $Result = array();
      if (preg_match_all('`([^{]*?){([^}]*?)}`', $Contents, $Matches, PREG_SET_ORDER)) {
         foreach ($Matches as $Match) {
            $SelectorString = $Match[1];
            $RulesString = $Match[2];

            // Parse the rules into an array.
            $RulesArray = explode(';', $RulesString);
            $Rules = array();
            foreach ($RulesArray as $RuleString) {
               $Rule = explode(':', $RuleString, 2);
               if (count($Rule) >= 2)
                  $Rules[trim($Rule[0])] = trim($Rule[1]);
            }

            // Add the rule to the result.
            $Result[] = array(
               self::SELECTOR_KEY => trim($SelectorString),
               self::RULES_KEY => $Rules);
         }
      }

      return $Result;
   }

   public static function RGB($Color) {
      if (preg_match('`#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})`i', $Color, $Matches)) {
         return array(hexdec($Matches[1]), hexdec($Matches[2]), hexdec($Matches[3]));
      }
   }

   public static function RGB2Hex($R, $G, $B) {
      $R = dechex($R);
      $G = dechex($G);
      $B = dechex($B);

      if (strlen($R) < 2)
         $R = '0'.$R;
      if (strlen($G) < 2)
         $G = '0'.$G;
      if (strlen($B) < 2)
         $B = '0'.$B;

      return strtolower("#$R$G$B");
   }

   /** Converts a color in RGB to HSV Values.
    *  This function has been adapted from a listing on stackoverflow.com
    *
    * @param int $R The red component of the color in the range [0,255].
    * @param int $G The green component of the color in the range [0,255].
    * @param int $B The blue component of the color in the range [0,255].
    * @return array An array in the form array(H, S, V) where HSV are floats in the range [0,1].
    */
   public static function RGB2HSV ($R, $G = NULL, $B = NULL) {
      if (is_array($R))
         list($R, $G, $B) = $R;
      elseif (is_string($R) && $R[0] == '#')
         list($R, $G, $B) = self::RGB($R);

      $R = $R / 255.0;
      $G = $G / 255.0;
      $B = $B / 255.0;
      $H = 0;
      $S = 0;
      $V = 0;
      $min = min(min($R, $G),$B);
      $max = max(max($R, $G),$B);
      $delta = $max - $min;

      $V = $max;

      if($delta == 0)
      {
         $H = NULL;
         $S = 0;
      }
      else
      {
         $S = $delta / $max;

         $dR = ((($max - $R) / 6) + ($delta / 2)) / $delta;
         $dG = ((($max - $G) / 6) + ($delta / 2)) / $delta;
         $dB = ((($max - $B) / 6) + ($delta / 2)) / $delta;

         if ($R == $max)
            $H = $dB - $dG;
         else if($G == $max)
            $H = (1/3) + $dR - $dB;
         else
            $H = (2/3) + $dG - $dR;

         if ($H < 0)
            $H += 1;
         if ($H > 1)
            $H -= 1;
      }

      return array($H, $S, $V);
   }

   public function ColorGroup($H) {
      if (is_string($H) && $H[0] == '#') {
         list($H, $S, $V) = self::RGB2HSV($H);
      }
      if ($H === NULL)
         return NULL;
      return round($H * $this->NumHues) % $this->NumHues;
   }

   /** Sort a css array by color.
    *
    * @param array $CssArray A css array returned from ParseCssFile() or FilterCssColors().
    * @return array An array in the form:
    * [#000000]: [selector: [property1: '', property2: !important, property3: '',...]]
    * [#ffffff]: [selector: [property1: '', property2: !important, property3: '',...]]
    * ...
    */
   public function SortCssByColor($CssArray) {
      $Result = array();

      foreach ($CssArray as $Rule) {
         if (!isset($Rule[self::RULES_KEY]) || !isset($Rule[self::SELECTOR_KEY]))
            continue;

         $Selector = $Rule[self::SELECTOR_KEY];
         $Rules = $Rule[self::RULES_KEY];

         foreach ($Rules as $Key => $Value) {
            // Get the color.
            if (preg_match('`(#[0-9a-f]{3,6}).*?(!important)?`i', $Value, $Matches)) {
               $Color = $Matches[1];
               $Options = GetValue(2, $Matches, '');
               $Result[$Color][self::SELECTOR_KEY][$Selector][$Key] = $Options;
               $Result[$Color]['hsv'] = self::RGB2HSV(self::RGB($Color));
            }
         }
      }
      return $Result;
   }

   public function CompareHSV($A, $B) {
      $HSV_A = $A['hsv'];
      $HSV_B = $B['hsv'];

      $H_A = $HSV_A[0];
      $H_B = $HSV_B[0];

      if ($H_A === NULL && $H_B !== NULL)
         return -1;
      elseif ($H_A !== NULL && $H_B === NULL)
         return 1;
      elseif ($H_A === NULL && $H_B === NULL)
         return self::Compare($HSV_A[2], $HSV_B[2]);
      else {
         // Chunk the hues so they are grouped.
         $H_A = $this->ColorGroup($H_A);
         $H_B = $this->ColorGroup($H_B);

         if ($H_A > $H_B)
            return 1;
         elseif ($H_A < $H_B)
            return -1;
         else {
//            if ($HSV_A[1] == $HSV_B[1])
//               return self::Compare($HSV_A[2], $HSV_B[2]);
//            else
//               return ($HSV_A[2] - $HSV_B[2]) / ($HSV_A[1] - $HSV_B[1]);
            return self::Compare($HSV_A[1], $HSV_B[1]);
         }
      }
   }

   public static function Compare($A, $B) {
      if ($A > $B)
         return 1;
      elseif ($A < $B)
         return -1;
      else
         return 0;
   }
}