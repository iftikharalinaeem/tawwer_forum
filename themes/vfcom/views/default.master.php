<?php
echo '<?xml version="1.0" encoding="utf-8"?>';
$Session = Gdn::Session();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
   <meta name="google-site-verification" content="T7dDWEaTeqt989RCxDJTfoOkbOADnRWLLJTauXxMHVA" />
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
			<div class="Solutions"><?php echo Anchor('<i class="Sprite SpriteSolutions"></i> Solutions', '/solutions', '', array('SSL' => FALSE)); ?></div>
			<div class="Features"><?php echo Anchor('<i class="Sprite SpriteFeatures"></i> Features', '/features', '', array('SSL' => FALSE)); ?></div>
			<div class="Blog"><?php echo Anchor('<i class="Sprite SpriteBlog"></i> Blog', '/blog', '', array('SSL' => FALSE)); ?></div>
			<div class="<?php echo $Session->IsValid() ? 'Account' : 'SignIn'; ?>"><?php
			$Text = 'Sign In';
			$Link = SignInUrl();
			if ($Session->IsValid()) {
				$Text = 'Sign Out';
				$Link = SignOutUrl();
				
				$Text = 'Account';
				$Link = 'account';
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
			if ($Session->IsValid())
				echo Anchor('Sign Out', SignOutUrl(), 'SignOut');
		?></div>
	</div>
</div>

   <div id="Frame">
      <div id="Body">
         <div id="Content"><?php
			if (in_array(strtolower($this->ControllerName), array('discussionscontroller', 'categoriescontroller'))) {
				echo '<div class="SearchForm">';
				$Form = Gdn::Factory('Form');
				$Form->InputPrefix = '';
				echo 
					$Form->Open(array('action' => Url('/search'), 'method' => 'get')),
					$Form->TextBox('Search'),
					$Form->Button('Search', array('Name' => '')),
					$Form->Close()
					.'</div>';
			}
			$this->RenderAsset('Content');
			?></div>
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
<?php /*
<script type="text/javascript">
document.write(unescape("%3Cscript src='" + ((document.location.protocol=="https:")?"https://snapabug.appspot.com":"http://www.snapengage.com") + "/snapabug.js' type='text/javascript'%3E%3C/script%3E"));</script><script type="text/javascript">
SnapABug.setButton("http://vanillaforums.com/applications/vfcom/design/images/help-tab.png");
SnapABug.addButton("34737bd0-1d78-43ac-be67-b2769cb5f6ae","0","30%");
</script>
		*/ ?>
</body>
</html>
