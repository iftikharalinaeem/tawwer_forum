<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>

<body id="{$BodyID}" class="{$BodyClass}">

<div id="Frame">
 <div id="Head">
   <div class="Banner Menu">
      <h1><a class="Title" href="{link path="/"}"><span>{logo}</span></a></h1>
      <ul id="Menu">
        {if CheckPermission('Garden.Settings.Manage')}
           <li><a href="{link path="dashboard/settings"}">Dashboard</a></li>
        {/if}
        <li><a href="{link path="discussions"}">Discussions</a></li>
        <li><a href="{link path="activity"}">Activity</a></li>
        {if $User.SignedIn}
           <li>
             <a href="{link path="messages/inbox"}">Inbox
             {if $User.CountUnreadConversations}<span>{$User.CountUnreadConversations}</span>{/if}</a>
           </li>
           <li>
             <a href="{link path="profile"}">{$User.Name}
             {if $User.CountNotifications}<span>{$User.CountNotifications}</span>{/if}</a>
           </li>
        {/if}
        {custom_menu}
        <li>{link path="signinout"}</li>
      </ul>
      <div id="Search">{searchbox}</div>
    </div>
  </div>

  <div id="Body">
    <div id="Content">
      {asset name="Content"}
    </div>
    <div id="Panel">{asset name="Panel"}</div>
  </div>
  <div id="Foot">
    <div>Powered by <a href="http://vanillaforums.org"><span>Vanilla</span></a></div>
    {asset name="Foot"}
 </div>
</div>

</body>
</html>