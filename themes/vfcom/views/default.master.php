<?php
echo '<?xml version="1.0" encoding="utf-8"?>';
$Session = Gdn::Session();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div id="Head" class="Wrapper">
   <div class="Center">
      <div class="Logo">
         <a href="/"><i class="Sprite SpriteLogo"><span>Vanilla Forums</span></i></a>
      </div>
      <div class="VFMenu">
			<div class="Home"><?php echo Anchor('<i class="Sprite SpriteHome"></i> Home', '/', '', array('SSL' => FALSE)); ?></div>
			<div class="Plans"><?php echo Anchor('<i class="Sprite SpritePlans"></i> Plans &amp; Pricing', '/plans', '', array('SSL' => FALSE)); ?></div>
			<div class="Features"><?php echo Anchor('<i class="Sprite SpriteFeatures"></i> Features', '/features', '', array('SSL' => FALSE)); ?></div>
			<div class="Blog"><?php echo Anchor('<i class="Sprite SpriteBlog"></i> Blog', '/blog', '', array('SSL' => FALSE)); ?></div>
			<div class="SignIn"><?php
			$Text = 'Sign In';
			$Link = SignInUrl();
			if ($Session->IsValid()) {
				$Text = 'Sign Out';
				$Link = SignOutUrl();
			}
			echo Anchor('<i class="Sprite SpriteSignIn"></i> '.$Text, $Link, '', array('SSL' => TRUE)); ?></div>
      </div>
	</div>
	<div class="Divider"></div>
	<div class="SubNav Wrapper">
		<div class="Center"><?php
			echo Anchor('All Support Discussions', '/discussions', 'Home');
			echo '&nbsp;';
			echo Gdn_Theme::Link('dashboard');
			if ($Session->IsValid())
				echo Anchor('Account', '/account', 'Account');
				
			echo Gdn_Theme::Link('profile', 'Profile', '<a href="%url" class="Profile">Profile</a>');
		?></div>
	</div>
</div>

   <div id="Frame">
      <div id="Body">
         <div id="Content"><?php $this->RenderAsset('Content'); ?></div>
         <div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
      </div>
   </div>

<div id="Foot" class="Foot Wrapper">
	<div class="Center">
      <?php
      echo Anchor('Partner/Referral Program', '/info/partnerprogram', '', array('SSL' => FALSE));
      echo Anchor('Terms of Service', '/info/termsofservice', '', array('SSL' => FALSE));
      echo Anchor('Privacy Policy', '/info/privacy', '', array('SSL' => FALSE));
      echo Anchor('Refund Policy', '/info/refund', '', array('SSL' => FALSE));
      echo Anchor("We're Hiring!", '/info/hiring', '', array('SSL' => FALSE));
      echo Anchor('About Us', '/info/faq', '', array('SSL' => FALSE));
      echo Anchor('Contact Us', '/info/contact', '', array('SSL' => FALSE));
		$this->RenderAsset('Foot');
      ?>
	</div>
</div>
<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>
