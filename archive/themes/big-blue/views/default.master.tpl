<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
   <div id="Frame">
      <div id="Head">
	  <div id="Header">	     
		<!--Load custom logo from banner options-->
     
      		<div class="Logo"><a href="{link path="/"}">{logo}</a></div>					

      <!-- end logo -->
      
         <div class="Menu">

                  <!-- Start menu -->
                  
                   {if CheckPermission('Garden.Settings.Manage')}
                  <div><a href="{link path="dashboard/settings"}"><span>Dashboard</span></a></div>
                  {/if}
			<div><a href="{link path="discussions"}"><span>Discussions</span></a></div>
			<div><a href="{link path="activity"}"><span>Activity</span></a></div>
             {if $User.SignedIn}
			<div><a href="{link path="messages/inbox"}"> <span>Inbox{if $User.CountUnreadConversations}<span>{$User.CountUnreadConversations}</span>{/if}</span></a></div>
            
            <div><a href="{link path="profile"}"><span>{$User.Name}</span>{if $User.CountNotifications} <span>{$User.CountNotifications}</span>{/if}</a></div>
     {/if}
     {custom_menu}
      {if $User.SignedIn}
             <div><a href="{link path="signinout" notag="true"}"><span>Sign Out</span></a></div>     
          {else}
             <div><a href="{link path="signinout" notag="true"}"><span>Sign In</span></a></div>  
             {/if}   
               
                  <!-- End menu -->
            
         </div>
         </div>
      </div>
      <div id="content_wrap">
      <div id="Body">
      
         <!-- Start body content: helper menu and discussion list -->
      
         <div id="Content">{asset name="Content"}</div>
         
         <!-- End body content -->
         
         <!-- Start panel modules: search, categories, and bookmarked discussions -->
         
         <div id="Panel">
		 
         <div id="Search">{searchbox}</div>
		 
		 {asset name="Panel"}
         
         </div>
         
         <!-- End panel -->

         
      </div>
      </div>
      <!-- Start foot -->
      
      <div id="Foot">
			<div><div class="vanilla-ico"></div> Powered by <a href="http://vanillaforums.org"><span>Vanilla</span></a></div>
    {asset name="Foot"}
		</div>
        
      <!-- End foot -->  
        
   </div>
</body>
</html>
