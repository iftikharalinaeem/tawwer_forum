<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  
  {asset name='Head'}
   
     <script type="text/javascript" src="/vanilla/themes/designer/js/slicker.js"></script>
     <script type="text/javascript" src="/vanilla/themes/designer/js/cookie.js"></script>
  
</head>
<body id="{$BodyID}" class="{$BodyClass}">
   <div id="Frame">
      <div id="Head">
         <div class="Menu">
				<!--Load custom logo from banner options-->
            
            	<h1 class="Title"><a href="{link path="/"}">{logo}</a></h1>
                
                  <!-- Start menu -->
                  
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
                  
                  <!-- End menu -->
                
            
         </div>
      </div>  
   </div>
   
   <!-- Start jQuery welcome window -->
   <!-- toggel welcome window -->
   
	<div id="toggle-wrapper">
		<div id="toggle">
     
     		<a href="#" id="slick-slidetoggle">Toggle Welcome</a>
     	</div>
     </div>

   <!-- Main jQuery window -->
   <div id="slickbox">
		<div id="intro-wrapper">
   			<div id="intro">
      
      			<div class="intro-text-head">Howdy! Welcome.</div>
     				<div class="intro-text">
     					Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam feugiat, turpis at pulvinar vulputate, erat 	libero tristique tellus, nec bibendum odio risus sit amet ante. Aliquam erat volutpat. 
    				</div>
     		
            <!-- flickr feed: replace the flcikr script with your own -->
 				<div class="intro-text-flickr">Community Showcase</div>
         		<div id="flickr">
         <script type="text/javascript" src="http://www.flickr.com/badge_code_v2.gne?count=10&amp;display=latest&amp;size=s&amp;layout=x&amp;source=user&amp;user=49912244@N05"></script>
         		</div>
     		</div>
    	</div>
        
        <!-- End main jQuery window -->
        
	</div>

      <div id="Body">
         
         <!-- Start body content: helper menu and discussion list -->
      
         <div id="Content">{asset name="Content"}</div>
         
         <!-- End body content -->
         
         <!-- Start panel modules: search, categories, and bookmarked discussions -->
         
         <div id="Panel">
		 
         <div id="Search">{searchbox}</div>
		 
		 {asset name="Panel"}

         
         <div class="Box">
         <h4 class="twitter">Twitter</h4>
         <ul>
		 <li>RT @nickla: Making tetris on skateboard... <a href="http://tumblr.com/xcva1me5k" rel="nofollow">http://tumblr.com/xcva1me5k</a> (RT @<a href="http://twitter.com/adii" class="aktt_username">adii</a>) <a href="http://twitter.com/vanilla/statuses/14174459234" class="aktt_tweet_time">39 mins ago</a></li>
		
		 <li class="aktt_more_updates"><a href="http://twitter.com/vanilla">More updates...</a></li>
</ul>
         </div>
         
         <div class="Box">
         <h4 class="facebook">Facebook</h4>
         <script type="text/javascript" src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US"></script><script type="text/javascript">FB.init("3ae2d7af638e5a1690d1d341ebdde25b");</script><fb:fan profile_id="109337975772721" stream="" connections="8" width="240"></fb:fan>
         </div>
         </div>
         
          <!-- End panel -->
          
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
