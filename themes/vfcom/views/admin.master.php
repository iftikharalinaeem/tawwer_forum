<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
   <div id="Frame">
      <div id="Head">
			<h1><?php echo Anchor(str_replace(array('http://', '/'), array('',''), Gdn::Config('Garden.Domain')).' <span>'.Gdn::Translate('← Visit Site').'</span>', '/'); ?></h1>
         <div class="User">
            <?php
			      $Session = Gdn::Session();
					$Authenticator = Gdn::Authenticator();
					if ($Session->IsValid()) {
						$Name = $Session->User->Name;
						$CountNotifications = $Session->User->CountNotifications;
						if (is_numeric($CountNotifications) && $CountNotifications > 0)
							$Name .= '<span>'.$CountNotifications.'</span>';
							
						echo Anchor($Name, '/profile/'.$Session->User->UserID.'/'.$Session->User->Name);
						echo Anchor(Gdn::Translate('Sign Out'), str_replace('{Session_TransientKey}', $Session->TransientKey(), $Authenticator->SignOutUrl()));
					} else {
						echo Anchor(Gdn::Translate('Sign In'), $Authenticator->SignInUrl($this->SelfUrl));
					}
				?>
         </div>
      </div>
      <div id="Body">
         <div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
         <div id="Content"><?php $this->RenderAsset('Content'); ?></div>
      </div>
      <div id="Foot">
			<div>
				Powered by <a href="http://vanillaforums.com"><span>Vanilla</span></a>
				- Problems? <?php echo Format::Email('support@vanillaforums.com'); ?>
			</div>
		</div>
   </div>
	<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>