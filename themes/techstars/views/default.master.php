<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
	<?php $this->RenderAsset('Head'); ?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#subscribe_btn').click(function() {
			$('#blog_signup').slideToggle('fast');
		});
	});
	</script>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div id="blog_signup" class="Hidden">
	<div class="wrapper clearfix" id="signup_info">
		<div class="subscribe_col">
			<p class="title">Subscribe via email:</p>
			<form action="http://www.feedburner.com/fb/a/emailverify" method="post" target="popupwindow" onsubmit="window.open('http://www.feedburner.com/fb/a/emailverifySubmit?feedId=614596', 'popupwindow', 'scrollbars=yes,width=550,height=520');return true"><input type="text" style="width:140px" name="email"/><input type="hidden" value="http://feeds.feedburner.com/~e?ffid=614596" name="url"/><input type="hidden" value="TechStars Blog" name="title"/><input type="hidden" name="loc" value="en_US"/><input type="submit" value="Subscribe" /></form>
			<p class="feedburner">Delivered by <a href="http://www.feedburner.com" target="_blank">FeedBurner</a></p>
		</div>
		<div class="subscribe_col">
			<p class="title">Subscribe via RSS</p>
			<p><a href="http://feeds.feedburner.com/TechstarsBlog"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/rss.gif" border="0" /></a><a href="http://feeds.feedburner.com/TechstarsBlog" class="rssText">Click here to get the RSS feed</a><p>
		</div>
	</div>
</div>

	<div id="header">
		<div class="wrapper">
			<div  id="logo">
				<a href="http://techstars.org">
					<img sm:iepng="true" alt="Techstars" src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/logo.png"/>
				</a>
			</div>
			<a href="#" id="subscribe_btn"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/subscribe.gif"alt="subscribe to our blog"/></a>
			<ul id="toolbar">
				<li><a href="http://www.techstars.org/about/"title="LearnmoreaboutTechstars">About</a></li>
				<li><a href="http://www.techstars.org/contact/"title="Contactus">contact</a></li>
				<li><a href="http://www.techstars.org/mediakit/"title="Mediakit">Media</a></li>
				<li><a href="http://www.techstars.org/search/"title="Mediakit">Search</a></li>
				<li><a href="http://www.techstars.org/apply/"title="Applytoourprogram">Apply</a></li>
				<li class="last"><a href="http://www.techstars.org/investor/"title="Investorinformation"class="green">Are you an investor?</a></li>
			</ul>
			<ul id="nav">
				<li ><a href="http://www.techstars.org/"><span>Home</span></a></li>
				<li ><a href="http://www.techstars.org/news/"><span>News</span></a></li>
				<li ><a href="http://www.techstars.org/details/"><span>Details</span></a></li>
				<li ><a href="http://www.techstars.org/mentors/"><span>Mentors</span></a></li>
				<li ><a href="http://www.techstars.org/schedule/"><span>Schedule</span></a></li>
				<li ><a href="http://www.techstars.tv"><span>TechStarsTV</span></a></li>
				<li ><a href="http://www.techstars.org/companies/"><span>Companies</span></a></li>
				<li><a href="http://www.techstars.org/blog/"><span>Blog</span></a></li>
			</ul>
		</div>
	</div>
   <div id="Frame">
<div id="Head">
         <div class="Menu">
            <?php
				
			      $Session = Gdn::Session();
					if ($this->Menu) {
						$this->Menu->AddLink('Dashboard', Gdn::Translate('Dashboard'), '/dashboard/settings', array('Garden.Settings.Manage'));
						$this->Menu->AddLink('Dashboard', Gdn::Translate('Users'), '/user/browse', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
						$this->Menu->AddLink('Activity', Gdn::Translate('Activity'), '/activity');
			         $Authenticator = Gdn::Authenticator();
						if ($Session->IsValid()) {
							$Name = $Session->User->Name;
							$CountNotifications = $Session->User->CountNotifications;
							if (is_numeric($CountNotifications) && $CountNotifications > 0)
								$Name .= '<span>'.$CountNotifications.'</span>';
								
							$this->Menu->AddLink('User', $Name, '/profile/{UserID}/{Username}', array('Garden.SignIn.Allow'));
							$this->Menu->AddLink('SignOut', Gdn::Translate('Sign Out'), $Authenticator->SignOutUrl(), FALSE, array('class' => 'NonTab'));
						} else {
							$this->Menu->AddLink('Entry', Gdn::Translate('Sign In'), $Authenticator->SignInUrl($this->SelfUrl), FALSE, array('class' => 'NonTab'), array('class' => 'Popup'));
						}
						echo $this->Menu->ToString();
					}

				/*
					$this->FireEvent('BeforeMenu');
					
					echo MenuItem('Dashboard', 'Dashboard', '/dashboard/settings', array('Garden.Settings.Manage'));
					echo MenuItem('Dashboard', 'Users', '/user/browse', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
					echo MenuItem('Activity', 'Activity', '/activity');
					
					$this->FireEvent('BeforeMenuUser');
					
					if ($Session->IsValid()) {
						$Name = $Session->User->Name;
						$CountNotifications = $Session->User->CountNotifications;
						if (is_numeric($CountNotifications) && $CountNotifications > 0)
							$Name .= '<span>'.$CountNotifications.'</span>';
               
						echo MenuItem('User', $Name, '/profile/{UserID}/{Username}', array('Garden.SignIn.Allow'));
						echo MenuItem('SignOut', 'Sign Out', $Authenticator->SignOutUrl(), FALSE, array('class' => 'NonTab'));
					} else {
						echo MenuItem('Entry', 'Sign In', $Authenticator->SignInUrl($Sender->SelfUrl));
					}
					
					$this->FireEvent('AfterMenu');
				*/
				?>
            <div id="Search"><?php
					$Form = Gdn::Factory('Form');
					$Form->InputPrefix = '';
					echo 
						$Form->Open(array('action' => Url('/search'), 'method' => 'get')),
						$Form->TextBox('Search'),
						$Form->Button('Go', array('Name' => '')),
						$Form->Close();
				?></div>
         </div>
      </div>
      <div id="Body">
			<div id="Content"><?php $this->RenderAsset('Content'); ?></div>
			<div id="Panel">
				<?php $this->RenderAsset('Panel'); ?>
			</div>
      </div>
		<div id="Foot"><?php $this->RenderAsset('Foot'); ?></div>
   </div>
	<div id="footer">
		<div class="wrapper">
			<h3 class="clearfix">Gold Sponsors</h3>
			<ul>
				<li><a href="http://sliceoflime.com" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/slice.gif" /></a></li>
				<li><a href="http://www.kkofirm.com/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/kko.gif" /></a></li>
				<li><a href="http://www.cooley.com/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/cooley.gif" /></a></li>
				<li><a href="http://www.hro.com/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/hro.gif" /></a></li>
				<li><a href="http://www.metzger.com/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/metzger.gif" /></a></li>
				<li><a href="https://www.square1financial.com/bank/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/square1.gif" /></a></li>
			</ul>

			<h3 class="clearfix">Silver Sponsors</h3>
			<ul id="silver_sponsors">
				<li><a href="http://www.svb.com/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/svb.gif" /></a></li>
				<li><a href="http://www.hollandhart.com/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/holland.gif" /></a></li>
				<li><a href="http://www.microsoft.com/BizSpark/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/bizSpark.gif" /></a></li>
 				<li><a href="http://www.dorsey.com/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/dorsey.gif" /></a></li>
			</ul>

			<h3 class="clearfix">Bronze Sponsors</h3>
			<ul id="bronze_sponsors">
				<li class="small"><a href="http://www.mediatemple.net/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/mt.gif" /></a></li>
				<li class="small"><a href="http://www.w3w3.com/" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/w3w3.gif" /></a></li>
				<li class="small"><a href="http://www.theresumator.com/home/s:techstars" target="_blank"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/sponsors/resumator.gif" /></a></li>
			</ul>
		</div>
		<a href="/boston/" class="iLoveBoston"><img src="http://techstars.org/images/iLoveBoston.gif" alt="I Love Boston!" /></a>
		<a href="/boulder/" class="boulder_rocks"><img src="http://www.techstars.org/wordpress/wp-content/themes/techstars_2009/images/layout/footer.jpg" /></a>
	</div>

</body>
</html>