<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
 <div id="TopBar">
 <div class="Center">
 
   <div class="Menu">                
                  
                  
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
                  
                 
            
       </div>
     </div>
   </div>
         
   <div id="Frame">  
   
   <div id="HeadWrapper">       
      <div id="Head">
      
      <!--Load custom logo from banner options-->
        
       <div class="Center">     
       <div class="Logo"><a href="{link path="/"}">{logo}</a></div>
		</div>
                
         
      </div>
   </div>
   
    

   <div class="Center">
   
   <!-- Start panel modules: search, categories, and bookmarked discussions -->
         
         <div id="Panel">
         
         
		 
         <div id="Search">{searchbox}</div>
         
         <div id="AdBlock">
          {text code="Ad 2" default="Paste Remote Ad Code Here."}
        </div>
		 
		 {asset name="Panel"}
         
         <div id="AdBlock">
          {text code="Twitter" default="Paste Remote Ad Code Here."}
        </div>
         
         <div id="AdBlock">
          {text code="Ad 3" default="Paste Remote Ad Code Here."}
        </div>
         
         </div>
         
         <!-- End panel -->
   
      <ul class="Reviews">
      <h4>Top Games Coming Soon</h4>
        <li>{text code="Review Soon 1" default="Paste review with link to post here."}</li>
        <li>{text code="Review Soon 2" default="Paste review with link to post here."}</li>
        <li>{text code="Review Soon 3" default="Paste review with link to post here."}</li>
        <li>{text code="Review Soon 4" default="Paste review with link to post here."}</li>
        <li>{text code="Review Soon 5" default="Paste review with link to post here."}</li>
        </ul>
       
        
     
          <ul class="Reviews">
          <h4>Top Games Out Now</h4>
        <li>{text code="Review Soon 1" default="Paste review with link to post here."}</li>
        <li>{text code="Review Soon 2" default="Paste review with link to post here."}</li>
        <li>{text code="Review Soon 3" default="Paste review with link to post here."}</li>
        <li>{text code="Review Soon 4" default="Paste review with link to post here."}</li>
        <li>{text code="Review Soon 5" default="Paste review with link to post here."}</li>
        </ul>
   
   		
         <!-- Start body content: helper menu and discussion list -->
      
         <div id="Content">
         <div id="AdBlockMid">
          {text code="Ad 1" default="Paste Remote Ad Code Here."}
        </div>
         
         
              {asset name="Content"}
         
         </div>
         
         <!-- End body content -->
         
         

         
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
