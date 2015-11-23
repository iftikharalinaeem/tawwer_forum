<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
  <div id="Frame">
	 <div id="HeaderCont">
		<div id="header">
        <a href="http://boagworld.com" id="logo"><img src="http://boagworld.com/wp-content/themes/BoagworldV2/images/logo.gif" alt="Boagworld Logo" /></a>
		  <ul id="nav-primary">
			 <li><a href="http://boagworld.com">Home</a></li>
			 <li><a href="http://boagworld.com/archive/">Blog</a></li>
			 <li><a href="http://boagworld.com/podcast/">Podcast</a></li>
			 <li><a href="http://boagworld.com/#product-nav">Products</a></li>
			 <li><a href="http://forum.boagworld.com">Forum</a></li>
			 <li><a href="http://boagworld.com/about/">About</a></li>
			 <li><a href="http://boagworld.com/contact/">Contact</a></li>
		  </ul>
        <div id="nav">
			 <span class="searchN">{searchbox}</span>
        </div>
		  <div id="info"> 
			 <div id="about"> 
				<h2>A podcast and forum for those who design, develop and run websites.</h2> 
				<p>Boagworld is not just a <a href="/">web design podcast</a>, it is also a thriving online community. Whether you build, design or run websites there are always people here to help. Whatever your question there is sure to be somebody with the answer.</p> 
			 </div> 
			 <ul id="subscribe">
            <li id="itunes"><a href="http://phobos.apple.com/WebObjects/MZStore.woa/wa/viewPodcast?id=81014881&amp;s=143444">subscribe via itunes</a></li>
            <li class="rss" id="mainRSS"><a href="http://feeds.feedburner.com/Boagworldcom-ForThoseManagingWebsites">subscribe via rss</a></li>
            <li class="emailSub" id="mainEmail">or get <strong>blog</strong> posts by <a href="http://feedburner.google.com/fb/a/mailverify?uri=Boagworldcom-ForThoseManagingWebsites&amp;loc=en_US">email</a></li>
          </ul>
		  </div> 
		</div>
	 </div>
	 <div class="Banner" id="Forum">
		<ul>
		  {dashboard_link}
		  {discussions_link}
		  {activity_link}
		  {inbox_link}
		  {profile_link}
		  {signinout_link} <li><a href="http://forum.boagworld.com/search">Search</a>
		  {custom_menu}
		</ul>
	 </div>
	 <div id="Body">
		<div class="Wrapper">
		  <div id="Advertisements">
			 <iframe width="130px" scrolling="no" height="865px" style="margin: 0pt; padding: 0pt;" id="ads" src="http://boagworld.com/advertising/"></iframe>
		  </div>
		  <div id="Panel">
			 {asset name="Panel"}
		  </div>
		  <div id="Content">
			 {asset name="Content"}
		  </div>
		</div>
	 </div>
	 <div id="sContent">&nbsp;</div>
	 <div id="Foot">
		{asset name="Foot"}
		<p id="copyright">
		  <a href="{vanillaurl}"><span>Discussions by Vanilla</span></a>
		  <a class="copyright" href="http://creativecommons.org/licenses/by-nc-sa/2.0/uk/">Copyright Notice</a>
		</p>
	 </div>
  </div>
</body>
</html>