<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  <!-- <script type="text/javascript" src="designer/js/jquery.js"></script> -->   
  
  
	<?php //$this->AddJsFile ('slicker.js'); ?>
    
   <?php $this->RenderAsset('Head'); ?>
   
     <script type="text/javascript" src="/vanilla/themes/designer/js/slicker.js"></script>
          <script type="text/javascript" src="/vanilla/themes/designer/js/cookie.js"></script>


   
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
   <div id="Frame">
      <div id="Head">
         <div class="Menu">
				<a class="Title" href="<?php echo Url('/'); ?>"> <?php echo Img('/themes/designer/design/logo.png'); ?></a>

            <?php
				
			      $Session = Gdn::Session();
					if ($this->Menu) {
						$this->Menu->AddLink('Dashboard', T('Dashboard'), '/dashboard/settings', array('Garden.Settings.Manage'));
						$this->Menu->AddLink('Dashboard', T('Users'), '/user/browse', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
						$this->Menu->AddLink('Activity', T('Activity'), '/activity');
			         $Authenticator = Gdn::Authenticator();
						if ($Session->IsValid()) {
							$Name = $Session->User->Name;
							$CountNotifications = $Session->User->CountNotifications;
							if (is_numeric($CountNotifications) && $CountNotifications > 0)
								$Name .= '<span>'.$CountNotifications.'</span>';
								
							$this->Menu->AddLink('User', $Name, '/profile/{UserID}/{Username}', array('Garden.SignIn.Allow'), array('class' => 'UserNotifications'));
							$this->Menu->AddLink('SignOut', T('Sign Out'), $Authenticator->SignOutUrl(), FALSE, array('class' => 'NonTab SignOut'));
						} else {
							$Attribs = array();
							if (Gdn::Config('Garden.SignIn.Popup'))
								$Attribs['class'] = 'SignInPopup';
								
							$this->Menu->AddLink('Entry', T('Sign In'), $Authenticator->SignInUrl($this->SelfUrl), FALSE, array('class' => 'NonTab'), $Attribs);
						}
						echo $this->Menu->ToString();
					}
				?>
                
            
         </div>

      </div>
      
      
     
   </div>
   
<div id="toggle-wrapper">
   
    <div id="toggle">
     
     <a href="#" id="slick-slidetoggle">Toggle Welcome</a>
     </div>
     </div>

   <div id="slickbox">

   
   <div id="intro-wrapper">
   
    <div id="intro">
      
      <div class="intro-text-head">Howdy! Welcome.</div>
     <div class="intro-text">
     Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam feugiat, turpis at pulvinar vulputate, erat libero tristique tellus, nec bibendum odio risus sit amet ante. Aliquam erat volutpat. 
     </div>
     
 <div class="intro-text-flickr">Community Showcase</div>
         <div id="flickr">
         <script type="text/javascript" src="http://www.flickr.com/badge_code_v2.gne?count=10&amp;display=latest&amp;size=s&amp;layout=x&amp;source=user&amp;user=49912244@N05"></script>
         </div>

     </div>
     
     
     
    </div>
    
    </div>

      <div id="Body">
         <div id="Content"><?php $this->RenderAsset('Content'); ?></div>
         <div id="Panel">
		 
        <div id="Search"><?php
					$Form = Gdn::Factory('Form');
					$Form->InputPrefix = '';
					echo 
						$Form->Open(array('action' => Url('/search'), 'method' => 'get')),
						$Form->TextBox('Search'),
						$Form->Button('Go', array('Name' => '')),
						$Form->Close();
				?></div>
		 
		 <?php $this->RenderAsset('Panel'); ?>
         
         <!--<div class="ad125"></div>
 		 <div class="ad125"></div>-->

         
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
         
         
         
          <!--<div class="ad125"></div>
 		 <div class="ad125"></div>
          <div class="ad125"></div>
 		 <div class="ad125"></div>-->
         
         </div>
         
      </div>
      <div id="Foot">
			<div><div class="vanilla-ico"><?php echo Img('/themes/minalla/design/vanilla_ico.png'); ?></div><?php
				printf(T('Powered by %s'), '<a href="http://vanillaforums.org"><span>Vanilla</span></a>');
			?></div>
			<?php $this->RenderAsset('Foot'); ?>
		</div>
   </div>
	<?php $this->FireEvent('AfterBody'); ?>
    
 
    
</body>
</html>
