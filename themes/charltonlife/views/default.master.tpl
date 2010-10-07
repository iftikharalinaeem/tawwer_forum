<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
 {asset name="Head"}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
 <div class="Banner">
  <div class="BannerWrapper">
	<h1><a href="{link path="/"}"><span>{logo}</span></a></h1>
   <div class="Buttons">
	 <div class="UserOptions">
	  <div>
	   {if $User.SignedIn}
       <a href="{link path="profile"}">
        {$User.Name}
        {if $User.CountNotifications}
         <span>{$User.CountNotifications}</span>
        {/if}
       </a>
       <a href="{link path="messages/inbox"}">Inbox
        {if $User.CountUnreadConversations}
         <span>{$User.CountUnreadConversations}</span>
        {/if}
       </a>
       {if CheckPermission('Garden.Settings.Manage')}
        <a href="{link path="dashboard/settings"}">Dashboard</a>
       {/if}
		{/if}
		{link path="signinout"}
		{if !$User.SignedIn}
			<a href="{link path="register"}">Apply for Membership</a>
		{/if}
     </div>
    </div>
    <ul>
     <li class="Home"><a href="http://www.charltonlife.com">Home</a></li>
     <li class="Articles"><a href="http://www.charltonlife.com/articles">Articles</a></li>
     <li class="Forum"><a href="http://www.charltonlife.com/forum">Forum</a></li>
     <li class="Contact"><a href="http://www.charltonlife.com/contact">Contact Us</a></li>
     <li class="Download"><a href="http://charltonlife.vanillaforums.com/help">Help</a></li>
    </ul>
	 <div id="Search">{searchbox}</div>
   </div>
  </div>
 </div>
 <div id="Frame">
  <div id="Body">
   <div id="Content">{asset name="Content"}</div>
   <div id="Panel">{asset name="Panel"}</div>
  </div>
  <div id="Foot">
  <div>Powered by <a href="http://vanillaforums.org"><span>Vanilla</span></a></div>
 </div>
</body>
</html>