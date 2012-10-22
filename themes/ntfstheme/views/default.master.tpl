<!DOCTYPEhtml PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
 {asset name="Head"}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
 <div class="Banner">
  <div class="BannerWrapper">
	<div class="Logo"><h1><a href="{link path="/"}"><span>{logo}</span></a></h1></div>
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
			<a href="{link path="entry/register"}">Create an Account</a>
		{/if}
    </div>
   </div>
        
   <div class="navigation">
       <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
          <td class="nav"><a class="ol3" href="http://www.ntfs.com">NTFS General Info</a></td>
          <td  class="nav"><a class="ol3" href="http://www.ntfs.com/products.htm">Data Recovery Software</a></td>
             <td  class="nav"><a class="ol3" href="http://www.ntfs.com/faq.htm">F.A.Q.</a></td>
          <td class="navforum"><a class="ol3" href="http://forum.ntfs.com">NTFS Forum</a></td>     
         </tr>
       </table> 
   </div>
   </div>
  </div>
    <div id="Frame">
  <div id="Body">
    <div class="Row">
      <div class="BreadcrumbsWrapper">{breadcrumbs}</div>
      <div class="Column PanelColumn" id="Panel">
         {module name="MeModule"}
         {asset name="Panel"}
      </div>
      <div class="Column ContentColumn" id="Content">

      {if $Discussions}
      <div class="SearchForm">{searchbox}</div>
      {/if}
         {asset name="Content"}
      </div>
    </div>
  </div>
  <div id="Foot">
    <div class="Row">
      <a href="{vanillaurl}" class="PoweredByVanilla">Powered by Vanilla</a>
      <a href=http://www.ntfs.com><span>NTFS 2010</span></a>
      {asset name="Foot"}
    </div>
  </div>
</div>
{event name="AfterBody"}
</body>
</html>