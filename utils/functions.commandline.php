<?php

$AccessToken = '9b765769312f055038d84815b1b40d45';

function Curl($Url) {
   // Curl the data to the new forum.
   $C = curl_init();
   curl_setopt($C, CURLOPT_URL, $Url);
   curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
   curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);

   $Result = curl_exec($C);
   
   if (!$Result) {
      throw new Exception(curl_error($C), curl_errno($C));
   }
   $Code = curl_getinfo($C, CURLINFO_HTTP_CODE);
   $ContentType = curl_getinfo($C, CURLINFO_CONTENT_TYPE);
   if ($ContentType == 'application/json') {
      $Result = json_decode($Result, TRUE);
      if ($Code != 200) {
         throw new Exception($Result['Exception'], $Result['Code']);
      }
   } elseif ($Code != 200) {
      throw new Exception($Result, $Code);
   }
   
   return $Result;
}

function ExceptionHandler($Ex) {
   echo $Ex->getMessage().' ('.$Ex->getCode().")\n";
}

set_exception_handler('ExceptionHandler');