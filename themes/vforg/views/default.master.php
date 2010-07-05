<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
	<div class="Banner">
		<div class="BannerWrapper">
			<h1><a href="<?php echo Url('/'); ?>"><span><?php echo Gdn_Theme::Logo(); ?></span></a></h1>
			<div class="Buttons">
				<div class="UserOptions">
					<div>
						<?php
							$Session = Gdn::Session();
							$Authenticator = Gdn::Authenticator();
							if ($Session->IsValid()) {
								$Name = '<em>'.$Session->User->Name.'</em>';
								$CountNotifications = 0;
								if (is_numeric($CountNotifications) && $CountNotifications > 0)
									$Name .= '<span>'.$CountNotifications.'</span>';
									
								echo Anchor(
									$Name,
									$CountNotifications > 0 ? '/profile/notifications' : '/profile/'.$Session->UserID.'/'.$Session->User->Name,
									'Username'
								);
		
								$Inbox = '<em>Inbox</em>';
								$CountUnreadConversations = 0;
								if (is_numeric($CountUnreadConversations) && $CountUnreadConversations > 0)
									$Inbox .= '<span>'.$CountUnreadConversations.'</span>';
						
								echo Anchor($Inbox, '/messages/all', 'Inbox');
		
								if ($Session->CheckPermission('Garden.Settings.Manage')) {
									echo Anchor('Dashboard', '/dashboard/settings', 'Dashboard');
								} else if ($Session->CheckPermission(array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'))) {
									echo Anchor('Users', '/user/browse', 'Dashboard');
								}
								
								echo Anchor('Sign Out', str_replace('{Session_TransientKey}', $Session->TransientKey(), $Authenticator->SignOutUrl()), 'Leave');
							} else {
								echo Anchor('Sign In', $Authenticator->SignInUrl($this->SelfUrl), 'SignInPopup');
								echo Anchor('Apply for Membership', $Authenticator->RegisterUrl($this->SelfUrl), 'Register');
							}
						?>
					</div>
				</div>
				<ul>
					<li class="Download"><?php echo Anchor('Download', '/download'); ?></li>
					<li class="Hosting"><?php echo Anchor('Hosting', '/hosting'); ?></li>
					<li class="Documentation"><?php echo Anchor('Documentation', '/docs'); ?></li>
					<li class="Community"><?php echo Anchor('Community', '/discussions'); ?></li>
					<li class="Addons"><?php echo Anchor('Addons', '/addons'); ?></li>
					<li class="Blog"><?php echo Anchor('Blog', '/blog'); ?></li>
					<li class="Home"><?php echo Anchor('Home', '/'); ?></li>
				</ul>
			</div>
		</div>
	</div>
   <div id="Frame">
      <div id="Body">
         <div id="Content"><?php $this->RenderAsset('Content'); ?></div>
         <div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
      </div>
      <div id="Foot">
			<div><?php
				$this->RenderAsset('Foot');
				printf(Gdn::Translate('Powered by %s'), '<a href="http://vanillaforums.org"><span>Vanilla</span></a>');
			?></div>
		</div>
   </div>
</body>
</html>