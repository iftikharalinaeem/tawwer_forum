<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
 

    <link rel="shortcut icon" href="http://69.70.63.54:8022/images/favicon.ico" />
    
     
  
</head>
<body id="{$BodyID}" class="{$BodyClass}">
  <div id="Frame">
  
  <div id="container-wrap">
  <div id="container">
{event name="TopBannerAd"}
  
  <div class="siteHeader">
        
        <div class="TitleBanner"><a class="Title" href="{link path="/"}">{logo}</a></div>

        <div id="socialNetworks">
            <a href=""><img src="{link path="/themes/Explorance/design/facebook.jpg" notag="1"}" /></a>&nbsp;&nbsp;
            <a href=""><img src="{link path="/themes/Explorance/design/in.jpg" notag="1"}" /></a>&nbsp;&nbsp;
            <a href=""><img src="{link path="/themes/Explorance/design/facebook.jpg" notag="1"}" /></a>
            <br />
            <a id="contactUsMenu" href="">contact us</a>      
        </div>

      </div>
    
         
    <!-- end exploarance header -->
    
	 <div class="Banner">
		<ul>
		  {dashboard_link} |
		  {discussions_link} |
		  {activity_link} |
		  {inbox_link} |
		  {profile_link} 
		  {custom_menu} 
		  {signinout_link}
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
		<div><a href="{vanillaurl}"><span>Powered by Vanilla</span></a></div>
		{asset name="Foot"}
	 </div>
	 
	  </div>
	 </div>
	 
  </div>  
  {event name="AfterBody"}
 </body>
</html>