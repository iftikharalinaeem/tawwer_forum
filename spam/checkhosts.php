<?php
error_reporting(E_ALL); //E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);

header('Content-Type: text/plain; charset=UTF-8');

set_include_path(__DIR__);

require_once './functions.php';

function main() {
   $mysqli = getSpamConnection();
   
   // Grab all of the domains.
   $sql = "select * from domain where ipaddress is null";
   $result = $mysqli->query($sql);
   while ($row = $result->fetch_assoc()) {
      $domain = $row['domain'];
      
      printf("%-40s", $domain);
      
      $start = microtime(true);
      $ipaddress = gethostbyname($domain);
      $time = microtime(true) - $start;
      
      printf("%-20s %s\n", $ipaddress, formatTimespan($time));
   }
}

main();