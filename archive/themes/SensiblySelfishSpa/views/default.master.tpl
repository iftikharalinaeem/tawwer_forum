<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">


<head>
  {asset name='Head'}
  {literal}
<link href="http://fonts.googleapis.com/css?family=Buda:light|Dancing+Script" rel="stylesheet" type="text/css" />
{/literal}

</head>
<body id="{$BodyID}" class="{$BodyClass}">
{event name="TopBannerAd"}
  <div id="Frame">
	 <div class="Banner">
		<ul>
		  {dashboard_link}
		  {discussions_link}
		  {activity_link}
		  <!--{inbox_link}
		  {profile_link}-->
		  {custom_menu}
		  <!--{signinout_link}-->
		</ul>
	 </div>
	 <div id="Body">
		<div class="Wrapper">
		  <div id="Panel">
		  {event name="TopPanelAd"}
			 <div class="SearchBox">{searchbox}</div>
			 {asset name="Panel"}
			 {event name="BottomPanelAd"}
		  </div>
		  <div id="Content">
			 {asset name="Content"}
		  </div>
		</div>
	 </div>
	 <div id="Foot">
	 {event name="BottomBannerAd"}
		<!--<div><a href="{vanillaurl}"><span>Powered by Vanilla</span></a></div>-->
		{asset name="Foot"}
	 </div>
  </div>
  {event name="AfterBody"}
</body>
</html>