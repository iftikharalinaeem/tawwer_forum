<?php

abstract class Task {

   protected $Client;
   public $TaskList;
   
   protected $Root;

   abstract protected function Run();

   public function __construct($RootFolder) {
      $this->Root = rtrim($RootFolder,'/');
   }
   
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
   
   // Forward to client
   public function __call($Method, $Args) {
      call_user_func_array(array($this->Client, $Method), $Args);
   }

}