<?php

/**
 * This file is part of Push.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Tim Gunter 
 */

function GetValue($Key, &$Collection, $Default = FALSE, $Remove = FALSE) {
	$Result = $Default;
	if(is_array($Collection) && array_key_exists($Key, $Collection)) {
		$Result = $Collection[$Key];
      if($Remove)
         unset($Collection[$Key]);
	} elseif(is_object($Collection) && property_exists($Collection, $Key)) {
		$Result = $Collection->$Key;
      if($Remove)
         unset($Collection->$Key);
   }
		
   return $Result;
}

function RandomString($Length, $Characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
   $CharLen = strlen($Characters) - 1;
   $String = '' ;
   for ($i = 0; $i < $Length; ++$i) {
     $Offset = rand() % $CharLen;
     $String .= substr($Characters, $Offset, 1);
   }
   return $String;
}