<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
  <div id="Frame">
  
	 <div class="MainMenu">
		<div class="Wrapper">
    	
    <div class="SearchBox">
    	<form method="get" action="/vanilla/search">
		<div>
			<input type="text" id="Form_Search" name="Search" value="" class="InputBox" />
			<input type="submit" id="Form_Go" value="" class="Button" />
		</div>
		</form>
	</div>


	<a class="Home" href="{link path="/"}"><span>{logo}</span></a>
	
	 <div class="Banner">
		<ul>
		  {dashboard_link}
		  {discussions_link}
		  {activity_link}
		  {inbox_link}
		  {profile_link}
		  {custom_menu}
		  {signinout_link}
		</ul>
	 </div>
	 </div>
	 </div>
  	<div class="Wrapper">
	 <div id="Body">
		  <div id="BodyMenu">
			 {asset name="BodyMenu"}
		  </div>
		  <div id="Panel">
			 {asset name="Panel"}
		  </div>
		  <div id="Content">
			 {asset name="Content"}
		  </div>
		</div>
	 <div id="Foot">
		<div><a href="{vanillaurl}"><span>Powered by Vanilla</span></a></div>
		{asset name="Foot"}
	 </div>
   </div>

  </div>
</body>
</html>