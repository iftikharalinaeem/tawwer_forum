<?php

/**
 * This file is part of Runner.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

abstract class Task {

   public $QualifiedName;
   public $TaskList;
   protected $Client;
   protected $Root;
   protected $Options;
   
   // Cross-task global settings
   protected static $CrossTaskSettings = array();

   abstract protected function Run();

   public function __construct() {
   }
   
   /**
    * Auto attach RootFolder and TaskList object to new tasks
    * 
    * @param string $RootFolder
    * @param TaskList $TaskList 
    */
   final public function Configure($RootFolder, &$TaskList) {
      $this->Root = $RootFolder;
      $this->TaskList = $TaskList;
   }
   
   /**
    *
    * @param Client $Client 
    */
   public function SandboxExecute(&$Client) {
      $this->Client = $Client;
      $this->Run();
   }
   
   protected function Cache($Key, $Value = NULL) {
      if (is_null($Value))
         return (array_key_exists($Key, $this->Client->GroupData)) ? $this->Client->GroupData[$Key] : NULL;
         
      return $this->Client->GroupData[$Key] = $Value;
   }
   
   protected function Uncache($Key) {
      unset($this->Client->GroupData[$Key]);
   }
   
   protected static function Set($Key, $Value = NULL) {
      if (is_null($Value) && array_key_exists($Key, self::$CrossTaskSettings))
         unset(self::$CrossTaskSettings[$Key]);
         
      return self::$CrossTaskSettings[$Key] = $Value;
   }
   
   protected static function Get($Key, $Default = NULL) {
      return (array_key_exists($Key, self::$CrossTaskSettings)) ? self::$CrossTaskSettings[$Key] : $Default;
   }
   
   public function Database() {
      return $this->Client->Database();
   }
   
   public function ClientRoot() { return $this->Client->ClientRoot; }
   public function ClientInfo($Param = NULL, $Default = NULL) { 
      if (!is_null($Param))
         return GetValue($Param, $this->Client->ClientInfo, $Default);
      return $this->Client->ClientInfo; 
   }
   public function ClientFolder() { return $this->Client->ClientFolder; }
   public function ConfigFile() { return $this->Client->ConfigFile; }
   
   public function GetFile($Filename) {
      $Path = TaskList::CombinePaths(TaskList::TASKS, $this->QualifiedName, $Filename);
      if (file_exists($Path))
         return $Path;
      return FALSE;
   }
   
   // Forward to client
   public function __call($Method, $Args) {
      return call_user_func_array(array($this->Client, $Method), $Args);
   }
   
   public static function ConfigOverlay($CommandDefaults, $CommandOptions) {
      $ShortOptions = "";
      $LongOptions = array();
      $ReverseOptions = array();
      
      foreach ($CommandOptions as $CommandKey => $CommandParameters) {
         // No Argument (default)
         $ParameterRequired = "";
         // Required
         if ($CommandParameters[2] === TRUE)
            $ParameterRequired = ':';
         // Optional
         if (is_null($CommandParameters[2]))
            $ParameterRequired = '::';

         if (!is_null($CommandParameters[0])) {
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
         $ParameterRawType = $CommandOptions[$CommandKey][2];
         $ParameterValue = ($ParameterRawType === TRUE || is_null($ParameterRawType)) ? $OptionValue : TRUE;
         
         $OverlayValues[$CommandKey] = $ParameterValue;
      }
      
      return array_merge($CommandDefaults, $OverlayValues);
   }
   
   public static function CheckConfigRules(&$Options, $Rules) {
      foreach ($Rules as $RuleName => $Rule) {
         $Required = $Rule[3];
         if ($Required && (!array_key_exists($RuleName, $Options) || is_null($Options[$RuleName])))
            throw new Exception("'{$RuleName}' is required but not provided, or null");
      }
      return TRUE;
   }
   
   public function Option($Key, $Default = NULL) {
      if (!is_array($this->Options) || !array_key_exists($Key, $this->Options)) return $Default;
      return $this->Options[$Key];
   }

}