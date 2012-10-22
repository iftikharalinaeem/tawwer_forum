#!/usr/bin/php
<?php

error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 'on');
ini_set('track_errors', 1);
putenv("MAILTO=todd@vanillaforums.com");

$StartTime = time();
echo "Crawling started ".date('r', $StartTime)."\n";

$Domain = "analytics.vanillaforums.com";
$AccessToken = FALSE;
require_once dirname(realpath(__FILE__)).'/functions.commandline.php';


for ($Offset = 1; $Offset < 10; $Offset++) {
   // Get a list of sites to crawl.
   $Data = Curl("http://$Domain/stats/browse.json?Advanced=1&Pingable=1&access_token=$AccessToken&offset=$Offset");
   $Sites = $Data['Sites'];

   if (count($Sites) == 0)
      echo "No sites to crawl.\n";

   // Loop through each site and crawl it.
   foreach ($Sites as $Site) {
      $VanillaID = $Site['VanillaID'];
      echo "Crawling {$Site['Hostname']}...";
      try {
      $Data = Curl("http://$Domain/crawl/site.json?vanillaid=$VanillaID&access_token=$AccessToken");
      echo round($Data['PercentComplete'] * 100)."%\n";
      } catch (Exception $Ex) {
         echo $Ex->getMessage()."\n";
      }
   }
   
   
   if (count($Sites) < 30)
      break;
}

$FinishTime = time();
$Seconds = $FinishTime - $StartTime;
$Minutes = floor($Seconds / 60);

$Time = sprintf('%0d:%0d', $Minutes, $Seconds - $Minutes * 60);

echo "Crawling started ".date('r', $FinishTime).", $Time\n";
