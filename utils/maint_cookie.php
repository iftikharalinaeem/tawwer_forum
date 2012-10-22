<?php

// Sets a cookie value that, when set, allows you to view sites under maintenance

/*
You must drop the following code in the garden installation's bootstrap.php file
in order to prevent others from seeing the site under maintenance, specifically:
/srv/www/vanilla_source and /srv/www/vanillaforumscom

Code:
$CookieValue = is_array($_COOKIE) && array_key_exists('vf_maintenance', $_COOKIE) ? TRUE : FALSE;
if (!$CookieValue) {
	 header("location: http://vanillaforums.com/maintenance/");
    exit();
}

*/

function ArrayValue($Needle, $Haystack, $Default = FALSE) {
	 $Return = $Default;
	 if (is_array($Haystack) === TRUE && array_key_exists($Needle, $Haystack) === TRUE)
	 	 $Return = $Haystack[$Needle];

	 return $Return;
}

$Set = ArrayValue('Set', $_GET);
$Destroy = ArrayValue('Destroy', $_GET);
$CookieName = 'vf_maintenance';
$CookiePath = '/';
$CookieDomain = strpos(ArrayValue('SERVER_NAME', $_SERVER, ''), 'chochy') !== FALSE ? '.chochy.com' : '.vanillaforums.com';
if ($Set) {
	 setcookie($CookieName, 'TRUE', 0, $CookiePath, $CookieDomain);
}
if ($Destroy) {
	 setcookie($CookieName, ' ', time() - 3600, $CookiePath, $CookieDomain);
    unset($_COOKIE[$CookieName]);
}


?>
<a href="maint_cookie.php?Set=TRUE">Set Cookie</a>
<br/><a href="maint_cookie?Destroy=TRUE">Destroy Cookie</a>