<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  
   
   <link rel="stylesheet" type="text/css" href="http://notmanhouse.com/wp-content/themes/enkelt_standard/style.css" media="screen" />
	<?php $this->RenderAsset('Head'); ?>
   <link rel="stylesheet" type="text/css" href="/themes/notman-house/design/custom.css" media="screen" />
   
   <script type="text/javascript" src="http://notmanhouse.com/wp-content/themes/enkelt_standard/library/js/superfish.js"></script>
<!--[if lt IE 8]><script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE8.js" type="text/javascript"></script><![endif]-->
	<script type="text/javascript">
	// Initiate jQuery Dropdown navigation
	jQuery(function(){ 
	    jQuery('ul.sf-menu').superfish(); 
	    jQuery('ul.sf-addmenu').superfish(); 
	});
	</script>
	<script type="text/javascript">
	jQuery(window).load(function(){
	    jQuery("#loopedSlider").loopedSlider({
	    		autoStart: 7000, 
		slidespeed: 1000, 
		autoHeight: false	    });
	});
	</script>
	<title>(Future) Home of the Web ... in Montreal ! | NOTMAN HOUSE</title><meta name="description" content="(Future) Home of the Web ... in Montreal !"><!-- Google Analytics Tracking by Google Analyticator 6.1: http://ronaldheft.com/code/analyticator/ -->
<script type="text/javascript">
	var analyticsFileTypes = [''];
	var analyticsEventTracking = 'enabled';
</script>
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', 'UA-16484930-1']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
</script>
   
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
   <div id="Frame">
   
 
   <div class="navigation-area clearfix">
	<div class="container_16">
    <div class="grid_16 main_menu">
   <!--Load custom logo from banner options-->
            
    <div class="logo-spot fl">
				    <h1 class="logo">
			<a href="http://notmanhouse.com/" title="NOTMAN HOUSE">
			    <img src="http://notmanhouse.com/wp-content/uploads/Notman_Logo_8.png" alt="NOTMAN HOUSE" border="0">
			</a>
			</h1><!--/.logo-->
				</div><!--/.logo-spot-->
		
		<ul class="sf-menu fr sf-js-enabled sf-shadow">
					    		<li class="page_item page-item-28"><a class="sf-with-ul" href="http://notmanhouse.com/what/" title="WHAT ?">WHAT ?</a>
					    		  <ul style="display: none; visibility: hidden;">
	<li class="page_item page-item"><a href="http://notmanhouse.com/what/idea-pods/" title="Private Spaces">Private Spaces</a></li>
	<li class="page_item page-item"><a href="http://notmanhouse.com/what/cafe/" title="Coworking Spaces">Coworking Spaces</a></li>
	<li class="page_item page-item"><a href="http://notmanhouse.com/what/the-house/" title="The House">The House</a></li>
	<li class="page_item page-item"><a href="http://notmanhouse.com/what/cafe-2/" title="Web Cafe">Web Cafe</a></li>
	<li class="page_item page-item"><a href="http://notmanhouse.com/what/event-spaces/" title="Public Spaces">Public Spaces</a></li>
</ul>
</li>
<li class="page_item page-item"><a class="sf-with-ul" href="http://notmanhouse.com/why/" title="WHY ?">WHY ?</a>
  <ul style="display: none; visibility: hidden;">
	<li class="page_item page-item"><a href="http://notmanhouse.com/why/the-future/" title="The Future !">The Future !</a></li>
	<li class="page_item page-item"><a href="http://notmanhouse.com/why/heritage/" title="The Past !">The Past !</a></li>
</ul>
</li>
<li class="page_item page-item"><a href="http://notmanhouse.com/where/" title="WHERE ?">WHERE ?</a></li>
<li class="page_item page-item"><a href="http://notmanhouse.com/who/" title="WHO ?">WHO ?</a></li>
<li class="page_item page-item"><a class="sf-with-ul" href="http://notmanhouse.com/how/" title="HOW ?">HOW ?</a>
  <ul style="display: none; visibility: hidden;">
	<li class="page_item page-item"><a href="http://notmanhouse.com/how/to-contact-us/" title="to get in contact">to get in contact</a></li>
</ul>
</li>
<li class="page_item page-item"><a class="sf-with-ul" href="http://notmanhouse.com/tech-community/" title="NEWS">NEWS</a>
  <ul style="display: none; visibility: hidden;">
	<li class="page_item page-item"><a href="http://notman.vanillaforums.com/" target="_blank" title="Community Stuff">Community</a></li></ul></li>
<li class="page_item page-item"><a href="http://www.maisonnotman.com/" target="_blank" title="fr">fr</a></li>
	 </ul><!-- /.sf-menu --> 
   
   </div>
   </div>
   </div>
   
      <div id="Head">
         <div class="Menu">
            <?php
			      $Session = Gdn::Session();
					if ($this->Menu) {
						$this->Menu->AddLink('Dashboard', Translate('Dashboard'), '/dashboard/settings', array('Garden.Settings.Manage'));
						// $this->Menu->AddLink('Dashboard', Translate('Users'), '/user/browse', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
						$this->Menu->AddLink('Activity', Translate('Activity'), '/activity');
			         $Authenticator = Gdn::Authenticator();
						if ($Session->IsValid()) {
							$Name = $Session->User->Name;
							$CountNotifications = $Session->User->CountNotifications;
							if (is_numeric($CountNotifications) && $CountNotifications > 0)
								$Name .= '<span>'.$CountNotifications.'</span>';
								
							$this->Menu->AddLink('User', $Name, '/profile/{UserID}/{Username}', array('Garden.SignIn.Allow'), array('class' => 'UserNotifications'));
							$this->Menu->AddLink('SignOut', Translate('Sign Out'), $Authenticator->SignOutUrl(), FALSE, array('class' => 'NonTab SignOut'));
						} else {
							$Attribs = array();
							if (Gdn::Config('Garden.SignIn.Popup'))
								$Attribs['class'] = 'SignInPopup';
								
							$this->Menu->AddLink('Entry', Translate('Sign In'), $Authenticator->SignInUrl($this->SelfUrl), FALSE, array('class' => 'NonTab'), $Attribs);
						}
						echo $this->Menu->ToString();
					}
				?>
           
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
         
         
         
         </div>
      </div>
    
      
   		<div class="footer-area clearfix">
<div class="container_16">

    <div class="grid_16 footer">
	    <div class="fl">&copy; 2010 NOTMAN HOUSE.</div><!-- /.fl -->
		<div class="fr">
		<ul>
						<li class="page_item page-item-28"><a href="http://notmanhouse.com/what/" title="WHAT ?">WHAT ?</a></li>
<li class="page_item page-item"><a href="http://notmanhouse.com/why/" title="WHY ?">WHY ?</a></li>
<li class="page_item page-item"><a href="http://notmanhouse.com/where/" title="WHERE ?">WHERE ?</a></li>
<li class="page_item page-item"><a href="http://notmanhouse.com/who/" title="WHO ?">WHO ?</a></li>
<li class="page_item page-item"><a href="http://notmanhouse.com/how/" title="HOW ?">HOW ?</a></li>
<li class="page_item page-item"><a href="http://notmanhouse.com/tech-community/" title="NEWS">NEWS</a></li>
<li class="last page_item page-item"><a href="http://www.maisonnotman.com/" target="_blank" title="fr">fr</a></li>
		<li class="powered"><a href="http://bizzthemes.com/" title="Designed by BizzThemes"><img src="http://notmanhouse.com/wp-content/themes/enkelt_standard/images/credits-trans.png" alt="BizzThemes" width="115" height="28"></a></li>
		</ul>
		</div><!-- /.fr -->
	</div><!-- /.grid_16 -->
	
    <div id="powered">
      
		<div class="vanilla-ico"></div> Powered by <a href="http://vanillaforums.org"><span>Vanilla</span></a></div>
	</div>
    
</div><!--/.container_16-->
</div><!--/.footer-area-->


	
    	
   </div>
	
</body>
</html>
