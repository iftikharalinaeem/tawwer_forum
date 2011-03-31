<?php

require_once('class.pushfront.php');
class Push {
   
   protected static $Args;
   
   protected static $Config;
   protected $Fronts = NULL;
   
   protected static $CommandOptions;
   
   public function __construct() {
      $ConfigFileOption = getopt('c:');
      if (!sizeof($ConfigFileOption))
         $ConfigFile = Push::Relative('conf/push.conf');
      else
         $ConfigFile = $ConfigFileOption['c'];
         
      if (!file_exists($ConfigFile) || !is_readable($ConfigFile))
         throw new Exception("Could not read config file: {$ConfigFile}");
         
      $ConfigDefaults = parse_ini_file($ConfigFile, FALSE, INI_SCANNER_RAW);
      $this->Overlay($ConfigDefaults);
      
      $this->CheckConfig();
   }
   
   public function Overlay($ConfigDefaults) {
      Push::$CommandOptions = array(
         'frontend pattern'         => array('f','frontend-pattern',TRUE),
         'frontend min'             => array(NULL,'min',TRUE),
         'frontend max'             => array(NULL,'max',TRUE),
         'frontend padding'         => array(NULL,'frontend-padding',TRUE),
         'frontend hostname'        => array('h','frontend-hostname', TRUE),
         'exclude frontends'        => array('e','exclude', TRUE),
         'source level'             => array('s','source-level', TRUE),
         'dry run'                  => array('d','dry-run', FALSE),
         'objects'                  => array('o','objects', TRUE),
         'remote user'              => array('u','user', TRUE),
         'remote password'          => array('p','password', TRUE)
      );
      
      $ShortOptions = "";
      $LongOptions = array();
      $ReverseOptions = array();
      
      foreach (Push::$CommandOptions as $CommandKey => $CommandParameters) {
         // No Argument (default)
         $ParameterRequired = "";
         // Required
         if ($CommandParameters[2] === TRUE)
            $ParameterRequired = ':';
         // Optional
         if (is_null($CommandParameters[2]))
            $ParameterRequired = '::';

         if (!is_null($CommandParameters[0])) {
            if ($CommandParameters[0] == 'c')
               throw new Exception("Command line option -c is reserved internally for specifying alternate config files");
            $ShortOptions .= $CommandParameters[0].$ParameterRequired;
            $ReverseOptions[$CommandParameters[0]] = $CommandKey;
         }
            
         if (!is_null($CommandParameters[1])) {
            $LongOption = $CommandParameters[1].$ParameterRequired;
            array_push($LongOptions, $LongOption);
            $ReverseOptions[$CommandParameters[1]] = $CommandKey;
         }
      }
      
      $OverlayValues = array();
      $Options = getopt($ShortOptions, $LongOptions);
      foreach ($Options as $Option => $OptionValue) {
         if (!array_key_exists($Option, $ReverseOptions)) continue;
         
         $CommandKey = $ReverseOptions[$Option];
         $ParameterRawType = Push::$CommandOptions[$CommandKey][2];
         $ParameterValue = ($ParameterRawType === TRUE || is_null($ParameterRawType)) ? $OptionValue : TRUE;
         
         $OverlayValues[$CommandKey] = $ParameterValue;
      }
      
      Push::$Config = array_merge($ConfigDefaults, $OverlayValues);
      $this->CheckConfig();
   }
   
   protected function CheckConfig() {
      $LocalPath = Push::Config('local path');
      $SourceLevel = Push::Config('source level');
      
      $FullPath = Push::CombinePaths($LocalPath, $SourceLevel);
      if (!is_dir($FullPath) || !is_readable($FullPath))
         throw new Exception("Could not read local source repository {$FullPath}");
      
      $Objects = Push::Config('objects');   
      $ObjectList = explode(',', $Objects);
      $Objects = array();
      foreach ($ObjectList as $Object) {
         $Object = trim($Object);
         $ObjectPath = Push::CombinePaths($FullPath, $Object);
         if (!is_dir($ObjectPath) || !is_readable($ObjectPath))
            throw new Exception("Could not read local source object {$ObjectPath}");
            
         $Objects[$Object] = $ObjectPath;
      }
      Push::$Config['compiled objects'] = $Objects;
   }
   
   public function Frontends() {
      if (is_null($this->Fronts))
         $this->FindFrontends();
      
      return $this->Fronts;
   }
   
   public function FindFrontends() {
      $this->Fronts = array();
      $FrontendPattern = Push::Config('frontend pattern');
      $FrontendSuffix = Push::Config('frontend hostname');
      
      $Min = Push::Config('frontend min');
      $Max = Push::Config('frontend max');
      $Padding = Push::Config('frontend padding');
      
      if ($Max == 0) {
         // Autodetect
         $Front = $Min;
         $ExclusionHost = Push::Config('frontend exclusion').".{$FrontendSuffix}";
         $ExclusionAddress = gethostbyname($ExclusionHost);
         while ($Front) {
            $PaddedFront = sprintf("%0{$Padding}d", $Front);
            $FrontendPrefix = sprintf($FrontendPattern, $PaddedFront);
            
            $FrontendHost = $FrontendPrefix.".{$FrontendSuffix}";
            $FrontendAddress = gethostbyname($FrontendHost);
            if ($FrontendAddress == $FrontendHost || $FrontendAddress == $ExclusionAddress)
               break;
            
            $this->Fronts[] = new PushFront($FrontendHost, $FrontendAddress);
            $Front++;
         }
      }
   }
   
   public function Execute() {
      foreach ($this->Frontends() as $Frontend) {
         $Frontend->Push();
      }
   }
   
   public static function Config($Parameter, $Default = NULL) {
      $Parameter = strtolower($Parameter);
      if (!array_key_exists($Parameter, Push::$Config))
         return $Default;
         
      return Push::$Config[$Parameter];
   }
   
   // Convenience method
   public static function Relative($Path) {
      static $Where = NULL;
      if (is_null($Where))
         $Where = getcwd();
      return Push::CombinePaths($Where, $Path);
   }
   
   // Convenience method
   public static function Pathify($Path) {
      return rtrim($Path, '/').'/';
   }
   
   // Convenience method
   public static function CombinePaths() {
      $Paths = func_get_args();
      $Delimiter = '/';
      
      $MungedPath = implode($Delimiter, $Paths);
      $MungedPath = str_replace(array($Delimiter.$Delimiter.$Delimiter, $Delimiter.$Delimiter), array($Delimiter, $Delimiter), $MungedPath);
      return str_replace('http:/', 'http://', $MungedPath);
   }
   
   public static function Question($Message, $Prompt, $Options, $Default) {
      echo "\n";
      if ($Message)
         echo $Message."\n";
         
      foreach ($Options as &$Opt)
         $Opt = strtolower($Opt);
         
      $HaveAnswer = FALSE;
      do {
         Push::_Prompt($Prompt, $Options, $Default);
         $Answer = trim(fgets(STDIN));
         if ($Answer == '') $Answer = $Default;
         $Answer = strtolower($Answer);
         
         if (in_array($Answer, $Options))
            $HaveAnswer = TRUE;
      } while(!$HaveAnswer);
      return $Answer;
   }
   
   protected static function _Prompt($Prompt, $Options, $Default) {
      echo "{$Prompt}";
      
      if (!sizeof($Options) && $Default !== FALSE && !is_null($Default)) {
         echo " [{$Default}]";
      }
      echo ": ";
      
      if (sizeof ($Options)) {
         $PromptOpts = array();
         foreach ($Options as $Opt)
            $PromptOpts[] = (strtolower($Opt) == strtolower($Default)) ? strtoupper($Opt) : strtolower($Opt);
         echo "(".implode(',',$PromptOpts).") ";
      }
   }
   
   public static function Input($Message, $Prompt, $Default) {
      echo "\n";
      if ($Message)
         echo $Message."\n";
         
      Push::_Prompt($Prompt, array(), $Default);
      $Answer = trim(fgets(STDIN));
      if ($Answer == '') $Answer = $Default;
      $Answer = strtolower($Answer);
      return $Answer;
   }
   
}