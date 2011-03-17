<?php echo '<?xml version="1.0" encoding="utf-8"?>';
$Session = Gdn::Session();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div class="modxcss">
	<div id="header">
		<a class="hidden" href="#main">Skip to content</a>
		<header class="container">
			<nav id="global">
				<a class="first active" href="<?php echo Url('/'); ?>">Forum Home</a>
				<a href="http://modx.com/">MODX.com</a>
				<a href="http://modx.com/help/">Help</a>
				<a class="clearfix" href="http://modx.com/search/">Search</a>
			</nav>
			<nav id="user">
				<?php
				if ($Session->IsValid())
					echo Gdn_Theme::Link('signinout', 'Sign Out');
				else
					echo '<a href="http://modx.com/login/?redirectBack=4">Login</a>';
				?>
			</nav>
			<nav id="logo_search">
				<a title="Open Source PHP Content Management System, Framework, Platform and More" id="logo" class="ir" href="/">MODX Open Source Content Management System, Framework, Platform and More.</a>
				<div id="search">
					<form accept-charset="utf-8" method="get" action="http://modx.com/search-results/">
						<label class="hidden" for="search_form_input">Search</label>
						<input type="text" title="Start typing and hit ENTER" value="" name="search" placeholder="Search keyphrase..." id="search_form_input" class="hasPlaceholder">
						<input type="submit" value="Go">
					</form>  
				</div>
			</nav>
		</header>
	</div>
	<div id="section_wrap">
		<header class="container">
			<nav id="section">
				<ul>
					<?php
					$Wrap = '<li class="level1"><a href="%url" class="level1"><span class="Title">%text</span> {desc}</a></li>';
					$FirstWrap = '<li class="first level1"><a href="%url" class="last level1"><span class="Title">%text</span> {desc}</a></li>';
					$LastWrap = '<li class="last level1"><a href="%url" class="last level1"><span class="Title">%text</span> {desc}</a></li>';
					if ($Session->IsValid()) {
						echo Gdn_Theme::Link('dashboard', 'Dashboard', str_replace('{desc}', 'Forum Administration', $FirstWrap));
						echo Gdn_Theme::Link('categories', 'Discussions', str_replace('{desc}', 'All Categories', $Wrap));
						echo Gdn_Theme::Link('activity', 'Activity', str_replace('{desc}', 'Recent Forum Activity', $Wrap));
						echo Gdn_Theme::Link('inbox', 'Inbox', str_replace('{desc}', 'Private Messages', $Wrap));
						$Name = $Session->User->Name;
						$CountNotifications = $Session->User->CountNotifications;
						if (is_numeric($CountNotifications) && $CountNotifications > 0)
							$Name .= ' '.$CountNotifications;
						
						echo Gdn_Theme::Link('profile', $Name, str_replace('{desc}', 'Profile, Wall & Account Info', $LastWrap));
					} else {
						echo Gdn_Theme::Link('categories', 'Discussions', str_replace('{desc}', 'All Discussion Categories', $FirstWrap));
						echo Gdn_Theme::Link('activity', 'Activity', str_replace('{desc}', 'Recent Forum Activity', $LastWrap));
					}
					?>
				</ul>   
			</nav>
		</header>
	</div>
</div>

	<div id="Frame">
		<div id="Messages">
			<?php $this->RenderAsset('Messages'); ?>
		</div>
      <div id="Body">
         <div id="Content"><?php $this->RenderAsset('Content'); ?></div>
         <div id="Panel">
				<div class="PanelBox">
					<?php $this->RenderAsset('Panel'); ?>
					<div class="Box SearchBox">
						<h4>Search the Forums</h4>
						<?php
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
      </div>
      <div id="Foot">
			<?php
				$this->RenderAsset('Foot');
				echo Wrap(Anchor(T('Powered by Vanilla'), C('Garden.VanillaUrl')), 'div');
			?>
		</div>
   </div>
<div class="modxcss">
	<footer>
      <a class="ir" id="top" href="http://modx.com/community/#header">Back to Top</a>
      <nav id="destinations">
         <div class="container">
            <section class="company first">
               <h3><a href="community/">Company</a></h3>
               <ul>
						<li><a href="about/blog/">Blog</a></li>
						<li><a href="about/contact/">Contact</a></li>
						<li><a href="about/media-center/">Media Center</a></li>
						<li><a href="services/">Services</a></li>
						<li><a href="partners/">Partners</a></li>
               </ul>
				</section>    
            <section class="support">
               <h3><a href="support/">Support</a></h3>
               <ul>
                  <li><a href="support/commercial/">Commercial Support</a></li>
                  <li><a href="support/forums/">Community Support</a></li>
                  <li><a href="support/documentation/">Documentation</a></li>
                  <li><a href="support/issues/">Bugs &amp; Suggestions</a></li>
               </ul>
            </section>
            <section class="developer">
               <h3><a href="developer/">Developer</a></h3>
               <ul>
						<li><a href="revolution/developer/source/">Get the Source</a></li>
						<li><a href="revolution/developer/contribute/">Contribute</a></li>
						<li><a href="revolution/developer/api/">API Documentation</a></li>
						<li><a href="learn/documentation/">Documentation</a></li>
						<li><a href="developer/issues/">Issue Tracker</a></li>
               </ul>
            </section>
            <section class="community">
               <h3><a href="community/">Community</a></h3>
               <ul>
						<li><a href="community/forums/">Forums</a></li>
						<li><a href="community/wall-of-fame/">Wall of Fame</a></li>
						<li><a href="community/wall-of-fame/support-modx/">Donate to MODX</a></li>
						<li><a href="community/spread-modx/">Spread MODx</a></li>
                  <li><a href="revolution/developer/contribute/">Contribute</a></li>
               </ul>
            </section>
            <section class="learn last clearfix">
               <h3><a href="learn/">Learn</a></h3>
               <ul>
						<li><a href="learn/documentation/">Documentation</a></li>
						<li><a href="learn/solutions/">Solutions</a></li>
						<li><a href="learn/gallery/">Made in MODX</a></li>
						<li><a href="learn/books/">Books</a></li>
               </ul>
            </section>
         </div>
      </nav>
      <section id="subscribe">
         <div class="container clearfix">
            <form method="post" action="http://modxcms.list-manage.com/subscribe/post" id="newsletter">
			      <h3>Subscribe to Our Newsletter</h3>
					<input type="hidden" value="08b25a8de68a29fe03a483720" name="u">
					<input type="hidden" value="848cf40420" name="id">
					<input type="hidden" id="source" value="www_4" name="source">
					<div class="clearfix">
						<label class="hidden" for="MERGE0">Your email</label>
						<input type="text" class="textbox hasPlaceholder" value="" name="MERGE0" id="MERGE0" required="" placeholder="you@example.com">
						<input type="submit" value="Sign up" name="Submit">
					</div>
					<p><a href="http://us1.campaign-archive.com/home/?u=08b25a8de68a29fe03a483720&amp;id=848cf40420">Read the previous issues</a></p>
            </form>
            <div class="clearfix" id="sponsors">
					<h3>Sponsors</h3>
					<a id="firehost" class="ir" href="http://firehost.com/">Firehost</a>
					<a id="mswss" class="ir" href="http://www.microsoft.com/web/websitespark/">Microsoft Websitespark</a>
					<a id="sponsor_modx" class="ir last" href="partners/sponsors/">Sponsor MODX</a>
            </div>
         </div>
      </section>
      <section id="copyright">
         <div class="clearfix container">
            <p><span><a href="policy/privacy/">Privacy Policy</a> | <a href="policy/terms-of-service/">Terms of Service</a> | Pixels by <a href="http://weareakta.com">AKTA Web Studio</a></span>&copy; 2005-2011 MODX. All rights reserved. <a href="policy/trademarks/">Trademark Policy</a> </p>
         </div>
      </section>
		<div id="post_body"></div>
	</footer>
</div>
	
	<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>