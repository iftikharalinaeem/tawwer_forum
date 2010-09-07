<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
	<div class="Wrap">
		<div class="Banner">
			<div class="BannerWrapper">
				<h1><a href="<?php echo Url('/'); ?>"><span><?php echo Gdn_Theme::Logo(); ?></span></a></h1>
				<ul>
					<!-- Put your own menu items here -->
					<li class="Home"><?php echo Anchor('Home', '/'); ?></li>
					<li class="Features"><?php echo Anchor('Features', '/features'); ?></li>
					<li class="Addons"><?php echo Anchor('Addons', '/addons'); ?></li>
					<li class="Community"><?php echo Anchor('Community', '/discussions'); ?></li>
					<li class="Documentation"><?php echo Anchor('Documentation', '/docs'); ?></li>
					<li class="Blog"><?php echo Anchor('Blog', '/blog'); ?></li>
					<li class="Services"><?php echo Anchor('Services', '/services'); ?></li>
					<li class="Download"><?php echo Anchor('Download', '/download'); ?></li>
					<!-- End Menu Items -->
				</ul>
			</div>
		</div>
		<div id="Frame">
			<div id="Body">
				<div id="Content"><?php $this->RenderAsset('Content'); ?></div>
				<div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
			</div>
			<div id="Foot">
				<div>
					<span style="float: right;">
						<?php
				      $Session = Gdn::Session();
						$Authenticator = Gdn::Authenticator();
						if ($Session->IsValid()) {
							echo Anchor($Session->User->Name, '/profile/'.$Session->User->UserID.'/'.Gdn_Format::Url($Session->User->Name));
							echo Wrap('&bull;', 'span', array('style' => 'font-size: 10px; padding: 0 10px;'));
							echo Anchor(T('Sign Out'), $Authenticator->SignOutUrl(), 'SignOut');
						} else {
							$CssClass = (C('Garden.SignIn.Popup') && strpos(Gdn::Request()->Url(), 'entry') === FALSE) ? 'SignInPopup' : '';
							echo Anchor(T('Sign In'), $Authenticator->SignInUrl($this->SelfUrl), $CssClass);
						}
						?>
					</span>
					<?php
					$this->RenderAsset('Foot');
					printf(Gdn::Translate('Powered by %s'), '<a href="http://vanillaforums.org"><span>Vanilla</span></a>');
				?></div>
			</div>
		</div>
	</div>
	<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>