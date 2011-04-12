<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name="Head"}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
  <div id="Frame">
	 <div class="Banner Menu">
		<h1><a class="Title" href="{link path="/"}"><span>{logo}</span></a></h1>
		<div class="nav">
		  <div class="links">
			 <a href="http://apps.facebook.com/causes/">Home</a>
			 <a href="http://apps.facebook.com/causes/causes">Causes</a>
			 <a href="http://wishes.causes.com/?bws=causes_header">Wishes</a>
			 <a href="http://causes.com/donate">Donate</a>
		  </div>
		  <div class="auth">
			 <span>
	         {link path="signinout"}
			 </span>
		  </div>
		</div>
		<div class="VanillaNav">
		  <ul>
         {dashboard_link}
         {link path="categories" text="Categories" format='<li><a href="%url" class="%class">%text</a></li>'}
         {discussions_link}
         {activity_link}
         {inbox_link}
         {profile_link}
		  </ul>
		</div>
	 </div>
	 <div id="Body">
		<div id="Panel">{asset name="Panel"}</div>
		<div id="Content">{asset name="Content"}</div>
	 </div>
	 <div id="Foot">
		<div class="FootWrapper">
		  <div id="vanilla_footer_links">
			 <a href="{vanillaurl}"><span>Discussions by Vanilla</span></a>
			 {dashboard_link}
		  </div>
		  <div id="site_footer_links">
			 <div class="tagline">&copy; Causes 2010. Anyone can change the world.</div>
			 <a href="http://apps.facebook.com/causes/">Home</a>
			 <a href="http://apps.facebook.com/causes/help">Help</a>
			 <a href="/widgets">Widgets</a>
			 <a target="blank" href="http://exchange.causes.com/jobs/">Jobs</a>
			 <a target="blank" href="http://nonprofits.causes.com">Nonprofits</a>
			 <a target="blank" href="http://exchange.causes.com">Causes Exchange</a>
			 <a target="blank" href="http://exchange.causes.com/about/">About</a>
			 <a href="http://causes.com/pages/tos">Terms</a>
			 <a target="blank" href="http://apps.facebook.com/causes/privacy">Privacy</a>
		  </div>
		</div>
	 </div>
  </div>
  {asset name="Foot"}
</body>
</html>