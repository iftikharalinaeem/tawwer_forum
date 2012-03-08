<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
<div id="Frame">
 <div id="Head">
   <div class="Banner Menu">
		<div class="IconMenu">
        <a class="Home Banner" href="{link path="/"}"><span>{logo}</span></a>
		  {inbox_link text='<strong>Inbox</strong>' format='<a href="%url" class="%class Inbox BannerSprites">%text</a>'}
		  {profile_link text='<strong>Notifications</strong>' format='<a href="%url/notifications" class="%class Notifications BannerSprites">%text</a>'}
		</div>
		<div class="SiteMenu">
		  <div class="MenuSearch" id="Search">{searchbox}</div>
		  <a class="Home" href="{link path="/"}">Home</a>
		  {user_link text="Profile" wrap=""}
		  {dashboard_link wrap=""}
		  {signinout_link wrap=""}
		</div>
    </div>
  </div>
  <div id="Body">
	 <div class="BodyMenu">
		{asset name="BodyMenu"}
		<ul class="MainMenu">
		  <li><a class="Activity" href="{link path="/activity"}">Recent Activity</a></li>
		  {discussions_link format='<li><a href="%url" class="Discussions">%text</a></li>'}
		  {bookmarks_link format='<li><a href="%url" class="Bookmarked MyBookmarks">%text</a></li>'}
		  {mydiscussions_link format='<li><a href="%url" class="Mine">%text</a></li>'}
		  {drafts_link format='<li><a href="%url" class="Drafts">%text</a></li>'}
		  {inbox_link format='<li><a href="%url" class="Inbox">%text</a></li>'}
		  {custom_menu}
		</ul>
	 </div>
	 <div class="ContentWrapper">
		<div id="Content">
		  {asset name="Content"}
		</div>
		<div id="Panel">
		  {asset name="Panel"}
		</div>
	 </div>
  </div>
  <div id="Foot">
    <div><a href="{vanillaurl}"><span>Powered by Vanilla</span></a></div>
    {asset name="Foot"}
 </div>
</div>
</body>
</html>