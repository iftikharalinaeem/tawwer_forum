<?php if (!defined('APPLICATION')) exit();

interface IZendeskHttpRequest {
   public function setOption($name, $value);

   public function execute();

   public function getInfo($name);

   public function close();
}

class ZendeskCurlRequest implements IZendeskHttpRequest {
   private $handle = null;

   public function __construct($url = '') {
      $this->handle = curl_init($url);
   }

   public function setOption($name, $value) {
      return curl_setopt($this->handle, $name, $value);
   }

   public function execute() {
      return curl_exec($this->handle);
   }

   public function getInfo($name) {
      return curl_getinfo($this->handle, $name);
   }

   public function close() {
      curl_close($this->handle);
   }

   public function __destruct() {
      $this->close();
   }
}