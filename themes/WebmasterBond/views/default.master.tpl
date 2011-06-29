<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
  <script src="http://www.google-analytics.com/ga.js" async="" type="text/javascript"></script>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/jquery-ui.min.js"></script>
  <script type="text/javascript" src="http://d1djb7jwq4h1ad.cloudfront.net/js/general_01.js"></script>
</head>
<body id="{$BodyID}" class="{$BodyClass}">
  <div id="Frame">
  
  <div class="main">
  <div class="blok_header">
  <div class="header">
  <div class="topbar">
  
  <div id="logo">
  	<a href="http://www.webmasterbond.com/" title="WebmasterBond Home Page"><img src="http://d1djb7jwq4h1ad.cloudfront.net/images/logo/logo501.png" alt="WebmasterBond" height="26" width="208"></a>
  </div>
  
  <div id="topuserbar">
  {if !$User.SignedIn }
  	<img src="http://d1djb7jwq4h1ad.cloudfront.net/images/other/default-user.png" alt="User Icon" height="12" width="9"> Welcome <b style="color:#FFF;">Guest</b>, {signin_link format='<a href="%url" class="%class">Login</a>'} or <a id="registerLink" href="http://www.webmasterbond.com/account/register/">Register</a>
   {else }
      Logged in as <b style="color:#FFF;">{$User.Name}</b>
      |
      {signinout_link format='<a href="%url" class="%class">%text</a>'}
   {/if}
  	</div>
  	
  	<div class="clr"></div>
  	
  	<div class="midbar">
  	
  	<div class="header_text">
  		<h1><span class="h1_1">
  			<span class="h1_2">Community Forums</span>
  			</span>
  		</h1>
  		<p></p>
  	</div>
  	
  	<div class="menu">
  		<ul>
  			<li><a href="http://www.webmasterbond.com/"><span>Home Page</span></a></li>
  			<li><a href="http://www.webmasterbond.com/advertisement/"><span>Advertisement</span></a></li>
  			<li><a href="http://www.webmasterbond.com/free-seo-tools/"><span>SEO Tools</span></a></li>
  			<li><a href="http://www.webmasterbond.com/free-domain-tools/"><span>Domain Tools</span></a></li>
  			<li><a href="http://www.webmasterbond.com/help/"><span>Help Center</span></a></li>
  			<li><a href="#" class="active"><span>Forum</span></a></li>
  		</ul>
  	</div>
  	
  	</div>
  	
  	<div class="clr"></div>
  	
  	</div>
  	</div>
  	</div>
  {event name="TopBannerAd"}
  <div id="container">
	 <div class="Banner">
		<ul>
		  {dashboard_link}
		  {discussions_link}
		  {activity_link}
		  {inbox_link}
		  {profile_link}
		  {custom_menu}
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
	 
	 	 <div class="clr"></div>

	 </div> <!-- container end -->
	 
	 <div class="FBG_blog">
	 	<div class="FBG_blog_resize">
	 		<a href="http://www.webmasterbond.com/help/contactus/"><img src="http://d1djb7jwq4h1ad.cloudfront.net/images/other/contactus.gif" alt="contact" border="0" height="42" width="162"></a>
	 		<div class="recent"><p><span>WebmasterBond: Bonding Webmasters WorldWide</span><br>If you have any questions or concerns regarding any of our tools or services, feel free to contact us now.</p>
	 		</div>
	 	<div class="clr"></div>
	 	</div>
	 	
	 	<div class="clr"></div>
	 	
	 </div>

	 
	 </div><!-- main end -->
	 
	 <div class="FBG">
	 	<div class="FBG_resize">
	 		<div class="left">
	 			<h2>WebmasterBond</h2>
	 			<ul>
	 				<li><a href="http://www.webmasterbond.com/aboutus/">About Us</a></li>
	 				<li><a href="http://www.webmasterbond.com/aboutus/mission/">Our Mission</a></li>
	 				<li><a href="http://www.webmasterbond.com/aboutus/careers/">Careers</a></li>
	 				<li><a href="http://www.webmasterbond.com/aboutus/terms-and-conditions/">Terms &amp; Conditions</a></li>
	 				<li><a href="http://www.webmasterbond.com/aboutus/privacy-policy/">Privacy Policy</a></li>
	 				<li><a href="http://www.webmasterbond.com/sitemap.xml">Sitemap</a></li>
	 			</ul>
	 		</div>
	 		<div class="left">
	 			<h2>Advertisement</h2>
	 				<ul>
	 					<li><a href="http://www.webmasterbond.com/advertisement/admaster/advertiser/">AdMaster Advertiser</a></li>
	 					<li><a href="http://www.webmasterbond.com/advertisement/admaster/publisher/">AdMaster Publisher</a></li>
	 					<li><a href="http://www.webmasterbond.com/advertisement/adserver/">Adserver</a></li>
	 					<li><a href="http://www.webmasterbond.com/advertisement/linkbond/advertiser/">LinkBond Advertiser</a></li>
	 					<li><a href="http://www.webmasterbond.com/advertisement/linkbond/publisher/">LinkBond Publisher</a></li>
	 					<li><a href="http://www.webmasterbond.com/advertisement/">&raquo; More Solutions</a></li>
	 				</ul>
	 		</div>
	 		<div class="left">
	 			<h2>SEO Tools</h2>
	 				<ul>
	 					<li><a href="http://www.webmasterbond.com/free-seo-tools/website-evaluator/">Website Evaluator</a></li>
	 					<li><a href="http://www.webmasterbond.com/free-seo-tools/link-analyzer/">Link Analyzer</a></li>
	 					<li><a href="http://www.webmasterbond.com/free-seo-tools/keyword-density-checker/">Keyword Density Tool</a></li>
	 					<li><a href="http://www.webmasterbond.com/free-seo-tools/pagerank-checker/">PageRank Checker</a></li>
	 					<li><a href="http://www.webmasterbond.com/free-seo-tools/monitor-website-rank/">Monitor Website Rank</a></li>
	 					<li><a href="http://www.webmasterbond.com/free-seo-tools/">&raquo; All SEO Tools</a></li>
	 				</ul>
	 			</div>
	 			<div class="left">
	 				<h2>Domain Tools</h2>
	 					<ul>
	 						<li><a href="http://www.webmasterbond.com/free-domain-tools/instant-domain-search/">Instant Domain Search</a></li>
	 						<li><a href="http://www.webmasterbond.com/free-domain-tools/whois-lookup/">Whois Lookup</a></li>
	 						<li><a href="http://www.webmasterbond.com/free-domain-tools/ip-lookup/">IP Lookup</a></li>
	 						<li><a href="http://www.webmasterbond.com/free-domain-tools/http-header-analyzer/">HTTP Header Analyzer</a></li>
	 						<li><a href="http://www.webmasterbond.com/free-domain-tools/domain-monitor/">Domain Monitor</a></li>
	 						<li><a href="http://www.webmasterbond.com/free-domain-tools/">&raquo; All Domain Tools</a></li>
	 					</ul>
	 			</div>
	 			<div class="left">
	 				<h2>Get in Touch</h2>
	 					<ul>
	 						<li><a href="http://www.webmasterbond.com/blog/">Blog</a><span style="color:#666;">&nbsp; / &nbsp;</span><a href="http://www.webmasterbond.com/community/">Community</a></li>
	 						<li><a href="http://www.webmasterbond.com/blog/news/">Latest News</a></li>
	 						<li><a href="http://www.webmasterbond.com/help/report-errors/">Report Errors</a></li>
	 						<li><a href="http://www.webmasterbond.com/help/feedback/">Feedback</a></li>
	 						<li><a href="http://www.webmasterbond.com/help/contactus/">Contact Us</a></li>
	 						<li><a href="http://www.webmasterbond.com/help/"><b>Help Center</b></a></li>
	 					</ul>
	 					<a href="http://www.webmasterbond.com/"><img src="http://d1djb7jwq4h1ad.cloudfront.net/images/other/rss_5.gif" alt="rss" border="0" height="16" width="16"></a> 
	 					<a href="http://www.webmasterbond.com/"><img src="http://d1djb7jwq4h1ad.cloudfront.net/images/other/rss_4.gif" alt="rss" border="0" height="16" width="16"></a> 
	 					<a href="http://www.webmasterbond.com/"><img src="http://d1djb7jwq4h1ad.cloudfront.net/images/other/rss_3.gif" alt="rss" border="0" height="16" width="16"></a> 
	 					<a href="http://www.webmasterbond.com/"><img src="http://d1djb7jwq4h1ad.cloudfront.net/images/other/rss_2.gif" alt="rss" border="0" height="16" width="16"></a> 
	 					<a href="http://www.webmasterbond.com/"><img src="http://d1djb7jwq4h1ad.cloudfront.net/images/other/rss_1.gif" alt="rss" border="0" height="16" width="16"></a>
	 			</div>
	 			<div class="clr">
	 		</div>
	 	</div>
	 	<div class="clr"></div>
	 	</div>
	 	
	 	<div class="footer">
	 		<div class="footer_resize">
	 			<p class="leftt">&copy; Copyright 2011 WebmasterBond. All Rights Reserved.</p>
	 			<p class="right"><a href="{vanillaurl}">Powered by Vanilla</p>
	 			<div class="clr"></div>
	 		</div>
	 		<div class="clr"></div>
	 	</div>
	 	<div id="popupContact"></div>
	 	<div id="backgroundPopup" onclick="disablePopup()"></div>
{event name="BottomBannerAd"}
	{asset name="Foot"}
 	
  </div>
  {event name="AfterBody"}
</body>
</html>