<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>

<body id="{$BodyID}" class="{$BodyClass}">

<div id="Frame">
 <div id="Head">
   <div class="Banner Menu">
      <h1><a class="Title" href="{link path="/"}"><span>{logo}</span></a></h1>
      <ul>
         {signinout_link class="leave"}
         {profile_link}
         {dashboard_link}
         {discussions_link}
      </ul>
    </div>
  </div>

  <div id="Body">
    <div id="About">
      <h2>About Mozilla Fans</h2>
      <p>Welcome to the Mozilla Fans question &amp; answer community. Find &amp;
      share info about Mozilla products with the thriving Mozilla
      community. Help us help you!</p>
    </div>
    <div id="Content">
      {asset name="Content"}
    </div>
    <div id="Panel">
      {asset name="Panel"}
      <div id="Search">{searchbox}</div>
    </div>
  </div>
  <div id="Foot">
    <div>
      <a href="{vanillaurl}"><span>Powered by Vanilla</span></a>
    </div>
    
    {asset name="Foot"}
 </div>
</div>
{event name="AfterBody"}
</body>
</html>