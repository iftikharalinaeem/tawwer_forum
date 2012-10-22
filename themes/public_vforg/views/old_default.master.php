<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
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
		{link path="signinout"}
		{if $User.SignedIn}
			{link path="register"}
		{/if}
     </div>
     <ul>
      <li class="Download"><a href="#">Download</a></li>
      <li class="Hosting"><a href="#">Hosting</a></li>
      <li class="Documentation"><a href="#">Documentation</a></li>
      <li class="Community"><a href="#">Community</a></li>
      <li class="Addons"><a href="#">Addons</a></li>
      <li class="Blog"><a href="#">Blog</a></li>
      <li class="Home"><a href="#">Home</a></li>
     </ul>
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
 </div>
</body>
</html>