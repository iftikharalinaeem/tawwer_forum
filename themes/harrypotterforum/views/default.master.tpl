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
        {if MultiCheckPermission(array('Garden.Settings.Manage', 'Garden.Settings.View'))}
           <li><a href="{link path="dashboard/settings"}">Dashboard</a></li>
        {/if}
        <li class="DiscussionsTab"><a href="{link path="discussions"}">Discussions</a></li>
        <li class="ActivityTab"><a href="{link path="activity"}">Activity</a></li>
        {if $User.SignedIn}
           <li>
             <a href="{link path="messages/inbox"}">Inbox
             {if $User.CountUnreadConversations}<span>{$User.CountUnreadConversations}</span>{/if}</a>
           </li>
           <li class="ProfileTab">
             <a href="{link path="profile"}">{$User.Name}
             {if $User.CountNotifications}<span>{$User.CountNotifications}</span>{/if}</a>
           </li>
        {/if}
        {custom_menu}
        <li class="AccountTab">{link path="signinout"}</li>
      </ul>
    </div>
  </div>

  <div id="Body">
    <div id="Panel">
      {asset name="Panel"}
      <div id="Search">{searchbox}</div>
      <a class="VanillaLink" href="{vanillaurl}"><span>Powered by Vanilla</span></a>
    </div>
    <div id="Content">
      {asset name="Content"}
    </div>
  </div>
  <div id="Foot">
    {asset name="Foot"}
 </div>
</div>

</body>
</html>