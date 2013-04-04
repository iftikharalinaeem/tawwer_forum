<?php

/**
 * Add links to a string of text/html.
 * 
 * @param string $html
 * @return type
 */
function autoLink($html) {
   $regex = "`(?:(</?)([!a-z]+))|(/?\s*>)|((?:https?|ftp)://[\@a-z0-9\x21\x23-\x27\x2a-\x2e\x3a\x3b\/;\x3f-\x7a\x7e\x3d]+)`i";

   $inTag = 0;
   $inAnchor = false;

   // This is the workhorse of the function. It replaces the links doing to the following:
   // - Makes sure we don't replace a link inside an attribute.
   // - Makes sure we don't create nested anchors.
   $callback = function ($matches) use ($inTag, $inAnchor) {
      $inout = $matches[1];
      $tag = strtolower($matches[2]);

      if ($inout == '<') {
         $inTag++;
         if ($tag == 'a')
            $inAnchor = TRUE;
      } elseif ($inout == '</') {
         $inTag++;
         if ($tag == 'a')
            $inAnchor = FALSE;
      } elseif ($matches[3])
         $inTag--;

      if (!isset($matches[4]) || $inTag || $inAnchor)
         return $matches[0];
      $url = $matches[4];

      // Strip punctuation off of the end of the url.
      $punc = '';
      if (preg_match('`^(.+)([.?,;:])$`', $url, $matches)) {
         $url = $matches[1];
         $punc = $matches[2];
      }
      
      // TODO: Check for special urls to embed.

      // Get human-readable text from url.
      $text = $url;
      if (strpos($text, '%') !== FALSE) {
         $text = rawurldecode($text);
         $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
      }

      $nofollow = ' rel="nofollow"'; // TODO: make option.

      $result = <<<EOT
<a href="$url" class="auto-link" target="_blank"$nofollow>$text</a>$punc
EOT;

         return $result;
      };

   $html = preg_replace_callback($regex, $callback, $html);

   return $html;
}

function formatTimespan($seconds) {
   if ($seconds < 0.0001)
      return number_format($seconds, 5) . "s";
   if ($seconds < 0.001)
      return number_format($seconds, 4) . "s";
   if ($seconds < 1)
      return number_format($seconds, 3) . "s";
   if ($seconds < 2)
      return number_format($seconds, 1) . "s";

   $days = (int) ($seconds / 86400);
   $seconds -= $days;
   $hours = (int) ($seconds / 3600);
   $seconds -= $hours;
   $minutes = (int) ($seconds / 60);
   $seconds -= $minutes;

   $result = '';
   if ($days)
      $result .= $days . 'd';
   if ($hours)
      $result .= ' ' . $hours . 'h';
   if ($minutes)
      $result .= ' ' . $minutes . 'm';
   if ($seconds)
      $result .= ' ' . round($seconds) . 's';

   return trim($result);
}

/**
 * @return mysqli
 */
function getSpamConnection() {
   static $mysqli = null;

   if (!isset($mysqli)) {
      $mysqli = new mysqli('localhost', 'root', '', 'spam');
      if ($mysqli->connect_errno) {
         echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
         return;
      }
   }

   return $mysqli;
}

/**
 * Return the domain parts out of a url
 * @param string $url
 * @return array an array of the (subdomain, name, tld).
 */
function parseDomainParts($url) {
   $slds = array("cy", "ro", "ke", "kh", "ki", "cr", "km", "kn", "kr", "ck",
      "cn", "kw", "rs", "ca", "kz", "rw", "ru", "za", "zm", "bz", "je", "uy",
      "bs", "br", "jo", "us", "bh", "bo", "bn", "bb", "ba", "ua", "eg", "ec",
      "et", "er", "es", "pl", "in", "ph", "il", "pe", "co", "pa", "id", "py",
      "ug", "ky", "ir", "pt", "pw", "iq", "it", "pr", "sh", "sl", "sn", "sa",
      "sb", "sc", "sd", "se", "hk", "sg", "sy", "sz", "st", "sv", "om", "th",
      "ve", "tz", "vn", "vi", "pk", "fk", "fj", "fr", "ni", "ng", "nf", "re",
      "na", "qa", "tw", "nr", "np", "ac", "af", "ae", "ao", "al", "yu", "ar",
      "tj", "at", "au", "ye", "mv", "mw", "mt", "mu", "tr", "mz", "tt", "mx",
      "my", "mg", "me", "mc", "ma", "mn", "mo", "ml", "mk", "do", "dz", "ps",
      "lr", "tn", "lv", "ly", "lb", "lk", "gg", "uk", "gn", "gh", "gt", "gu",
      "jp", "gr", "nz", "au", "tn");

   $host = parse_url($url, PHP_URL_HOST);
   if (!$host)
      return array('', '', '');

   $parts = explode('.', $host);
   $count = count($parts);
   if ($count == 1)
      return array('', $parts[0], 'com');
   if ($count == 2) {
      array_unshift($parts, '');
      return $parts;
   }

   $subdomain = '';
   if (in_array($parts[$count - 2], $slds)) {
      // This is a two part tld.
      $tld = implode('.', array_slice($parts, $count - 2));
      $name = $parts[$count - 3];
      if ($count > 3)
         $subdomain = implode('.', array_slice($parts, 0, $count - 3));
   } else {
      $tld = $parts[$count - 1];
      $name = $parts[$count - 2];
      if ($count > 2)
         $subdomain = implode('.', array_slice($parts, 0, $count - 2));
   }

   return array($subdomain, $name, $tld);
}

function touchValue($key, &$array, $default) {
   if (!array_key_exists($key, $array))
      $array[$key] = $default;
}
