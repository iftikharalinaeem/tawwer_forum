<?php

abstract class Task {

   protected $Client;
   public $TaskList;
   protected $Root;
   
   protected $SetupOK = FALSE;

   abstract protected function Run();

   public function __construct() {
   }
   
   /**
    * 
    * @
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
         return (array_key_exists($Key, $this->TaskList->GroupData)) ? $this->TaskList->GroupData[$Key] : NULL;
         
      return $this->Client->GroupData[$Key] = $Value;
   }
   
   protected function Uncache($Key) {
      unset($this->Client->GroupData[$Key]);
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
   
   // Forward to client
   public function __call($Method, $Args) {
      call_user_func_array(array($this->Client, $Method), $Args);
   }

}