<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
 
  {literal}

    
    <script type='text/javascript' src='http://www.carteroosterhouse.com/Websites/carteroosterhouse/templates/readyforcms16march11/menu/example.js'></script>

    
  {/literal}
  
</head>
<body id="{$BodyID}" class="{$BodyClass}">
  <div id="Frame">
  
  <div id="container-wrap">
  <div id="container">
  {event name="TopBannerAd"}
  <div class="TopBanner">

	<div class="logo">
	<img src="http://www.carteroosterhouse.com/Websites/carteroosterhouse/templates/readyforcms16march11/images/logo.jpg" border="0" />
	</div><!-- logo end -->
	
	<div class="newsletter">
	<table border="0" cellspacing="0" cellpadding="4">
        <tbody>
            <tr class="formelements" valign="top">
                <td style="height: 10px; text-align: left; vertical-align: middle;">
                <p style="text-align: right;"><label for="xkhtri-xkhtri">Get email updates from Carter:</label></p>
                </td>
                <td style="text-align: left; vertical-align: middle;">

                <p style="text-align: right;"><input name="cm-xkhtri-xkhtri" class="formc" id="xkhtri-xkhtri" style="width: 145px; height: 12px;" type="text" size="15" value="Enter Email Address" /></p>
                </td>
                <td style="text-align: left; vertical-align: middle;">
                <input name="imageField" id="imageField" type="image" src="http://www.carteroosterhouse.com/Websites/carteroosterhouse/Images/go.jpg" onclick="LCMS.Util.removeInputs('1975863_809928');document.getElementById('frm').action='http://blast.nexfusion.com/t/r/s/xkhtri/'; document.getElementById('frm').enctype='';" class="go" /></td>
            </tr>
        </tbody>
    </table>
	</div> <!-- newsletter end -->
	
	<div class="social">
	<img src="http://www.carteroosterhouse.com/Websites/carteroosterhouse/templates/readyforcms16march11/images/fbtw.jpg" width="75" height="30" border="0" usemap="#Map" />
        
          <map name="Map" id="Map">
            <area shape="rect" coords="11,4,32,24" href="http://www.facebook.com/people/Carter-Oosterhouse/797227519" target="_blank" />
            <area shape="rect" coords="36,6,55,25" href="http://twitter.com/c_oosterhouse" target="_blank" />

          </map>
	</div><!-- social end -->
	
	<div class="nav-wrap">
              <ul class="group" id="example-one">
                <li><a href="http://www.carteroosterhouse.com">Home</a></li>
                <li><a href="http://www.carteroosterhouse.com/ontv"> On TV </a></li>

                <li><a href="http://www.carteroosterhouse.com/cartersupports"> Carter SUPPORTS </a></li>
                <li><a href="http://www.carteroosterhouse.com/carterskids"> Carter's Kids </a></li>
                <li><a href="http://www.carteroosterhouse.com/conserve"> Conserve </a></li>
                <li class="current_page_item"><a href="http://www.carteroosterhouse.com/fanclub"> Fan Club </a></li>
                <li><a href="http://www.carteroosterhouse.com/media"> MEDIA</a></li>

                <li><a href="http://www.carteroosterhouse.com/blog"> Blog </a></li>
              </ul>
            </div>      <!-- Nav end -->
            
            <div class="breaker"><img src="http://www.carteroosterhouse.com/Websites/carteroosterhouse/templates/readyforcms16march11/images/orangebreaker.jpg" /></div>

 </div>
  
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
	 <table>
	 <tr>
        <td width="160" align="left" valign="top" class="lighttextheader">Sitemap</td>
        <td width="160" align="left" valign="top" class="lighttextheader">Social Media</td>
        <td width="160" align="left" valign="top" class="lighttextheader">Information</td>

      </tr>
      <tr>
        <td align="left" valign="top"><a href="http://www.carteroosterhouse.com" class="Mainmenusub">Home </a><br />
          <a href="http://www.carteroosterhouse.com/ontv" class="standardLink">On TV </a><br />
          <a href="http://www.carteroosterhouse.com/cartersupports">Carter Supports </a><br />
          <a href="http://www.carteroosterhouse.com/carterskids"> Carter's Kids </a><br />

          <a href="http://www.carteroosterhouse.com/conserve">Conserve </a><br />
          <a href="http://www.carteroosterhouse.com/fanclub">Fan Club </a><br />
          <a href="http://www.carteroosterhouse.com/media">Media </a><br />
          <a href="http://www.carteroosterhouse.com/blog">Blog</a></td>
        <td align="left" valign="top"><a href="http://www.facebook.com/people/Carter-Oosterhouse/797227519" class="standardLink">Facebook</a><br />
          <a href="http://www.youtube.com" class="standardLink">Youtube</a><br />

          <a href="http://twitter.com/c_oosterhouse">Twitter</a><a href="#"></a><br /></td>
        <td align="left" valign="top"><a href="http://www.carteroosterhouse.com/contact" class="standardLink">Contact</a><br /></td>
        <td>&nbsp;</td>
      </tr>
    </table>
	 
		<div><a href="{vanillaurl}"><span>Powered by Vanilla</span></a></div>
		{asset name="Foot"}
	 </div>
	 
	 </div>
	 </div>
	 
 </div>
 {event name="AfterBody"}
</body>
</html>