<?php

require_once('class.pushfront.php');
class Push {
   
   const LOG_L_FATAL = 1;
   const LOG_L_WARN = 2;
   const LOG_L_NOTICE = 4;
   const LOG_L_INFO = 8;
   
   const LOG_O_NONEWLINE = 1;
   
   protected static $Args;
   
   protected static $Config;
   protected $Fronts = NULL;
   protected $PushRoot = NULL;
   
   protected static $CommandOptions;
   
   public function __construct() {
      $ConfigFileOption = getopt('c:');
      if (!sizeof($ConfigFileOption))
         $ConfigFile = Push::ExecRelative('conf/push.conf');
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
         'source tag'               => array('s','source-tag', TRUE),
         'dry run'                  => array('d','dry-run', FALSE),
         'objects'                  => array('o','objects', TRUE),
         'remote user'              => array('u','user', TRUE),
         'remote password'          => array('p','password', TRUE),
         'log level'                => array('l','log-level',TRUE)
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
      $LocalPath = Push::LocalPath();
      $SourceTag = Push::Config('source tag');
      
      $FullPath = Push::CombinePaths($LocalPath, $SourceTag);
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
      
      $ExcludedFrontends = Push::Config('exclude frontends', '');
      $ExcludedFrontends = explode(',', $ExcludedFrontends);
      
      if ($Max == 0) {
         // Autodetect
         $Front = $Min - 1;
         $ExclusionHost = Push::Config('frontend exclusion').".{$FrontendSuffix}";
         $ExclusionAddress = gethostbyname($ExclusionHost);
         while (TRUE) {
            $Front++;
            $PaddedFront = sprintf("%0{$Padding}d", $Front);
            $FrontendPrefix = sprintf($FrontendPattern, $PaddedFront);
            $FrontendHost = $FrontendPrefix.".{$FrontendSuffix}";
            
            // Exclusions
            if (in_array($FrontendPrefix,$ExcludedFrontends) || in_array($FrontendHost, $ExcludedFrontends))
               continue;
            
            $FrontendAddress = gethostbyname($FrontendHost);
            if ($FrontendAddress == $FrontendHost || $FrontendAddress == $ExclusionAddress)
               break;
            
            $this->Fronts[] = new PushFront($FrontendHost, $FrontendAddress);
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
   
   public static function LocalPath() {
      static $LocalPath = FALSE;
      
      if ($LocalPath === FALSE) {
         $ConfLocalPath = Push::Config('local path');
         $ParseLocal = (substr($ConfLocalPath, 0, 1) == '~') ? TRUE : FALSE;
      
         if ($ParseLocal) {
            $Response = Push::PushStagingRoot().substr($ConfLocalPath, 1);
         } else {
            $Response = $ConfLocalPath;
         }
         
         // Assign to local static variable
         $LocalPath = Push::Pathify($Response);
      }
      return $LocalPath;
   }
   
   public static function PushExecRoot() {
      static $PushExecRoot = FALSE;
      if ($PushExecRoot === FALSE) {
         $PushExecRoot = exec('pwd');
      }
      return $PushExecRoot;
   }
   
   public static function PushStagingRoot() {
      static $PushRoot = FALSE;
      if ($PushRoot === FALSE) {
         $PushFolderComponents = explode('/', Push::PushExecRoot());
         array_pop($PushFolderComponents);
         $PushRoot = implode('/', $PushFolderComponents);
      }
      return $PushRoot;
   }
   
   // Convenience method
   public static function ExecRelative($Path) {
      static $Where = NULL;
      if (is_null($Where))
         $Where = Push::PushExecRoot();
      return Push::CombinePaths($Where, $Path);
   }
   
   // Convenience method
   public static function Relative($Path) {
      static $Where = NULL;
      if (is_null($Where))
         $Where = Push::PushStagingRoot();
      return Push::CombinePaths($Where, $Path);
   }
   
   // Convenience method
   public static function Pathify($Path) {
      return rtrim($Path, '/').'/';
   }
   
   public static function UnPathify($Path) {
      return rtrim($Path, '/');
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
   
   public static function Log($Level, $Message, $Options = 0) {
      static $LoggingLevel = FALSE;
      
      if ($LoggingLevel === FALSE)
         $LoggingLevel = Push::Config('log level', 1);
      
      if ($LoggingLevel & $Level) {
         echo $Message;
         if (!($Options & Push::LOG_O_NONEWLINE))
            echo "\n";
      }
   }
   
}