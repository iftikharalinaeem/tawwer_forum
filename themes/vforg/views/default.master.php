<?php
echo '<?xml version="1.0" encoding="utf-8"?>';
$Session = Gdn::Session();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
   <meta name="google-site-verification" content="T7dDWEaTeqt989RCxDJTfoOkbOADnRWLLJTauXxMHVA" />
	<meta name="alexaVerifyID" content="cn9a0DueZ_LLZItlOjDwtxnL2to" />
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div id="Head" class="Wrapper">
   <div class="Center">
      <div class="Logo">
         <a href="/"><i class="Sprite SpriteLogo"><span>Vanilla Forums</span></i></a>
      </div>
      <div class="VFMenu">
			<div class="Home" title="An overview of Vanilla."><?php echo Anchor('Home', '/', ''); ?></div>
			<div class="Addons" title="Browse Vanlla addons."><?php echo Anchor('Addons', '/addons'); ?></div>
			<div class="Community" title="Get support from other people that use Vanilla."><?php echo Anchor('Community', '/discussions'); ?></div>
			<div class="Documentation" title="Read through Vanilla's documentation."><?php echo Anchor('Documentation', '/docs'); ?></div>
			<div class="Blog" title="See what the Vanilla team is up to."><?php echo Anchor('Blog', 'http://vanillaforums.com/blog'); ?></div>
			<div class="Hosting" title="Host with us!"><?php echo Anchor('Hosting', 'http://vanillaforums.com'); ?></div>
			<div class="Download" title="Download the latest version of Vanilla."><?php echo Anchor('Download', '/download'); ?></div>
      </div>
	</div>
	<div class="Divider"></div>
	<div class="SubNav Wrapper">
		<div class="Center"><?php
			// echo Anchor('Community Discussions', '/discussions', 'Home');
			echo '&nbsp;';
			echo Gdn_Theme::Link('dashboard');
         echo Gdn_Theme::Link('activity', 'Activity', '<a href="%url" class="Activity">Activity</a>');
			echo Gdn_Theme::Link('profile', 'Profile', '<a href="%url" class="Profile">Profile</a>');
			$Text = 'Sign In';
			$Link = SignInUrl();
			if ($Session->IsValid()) {
				$Text = 'Sign Out';
				$Link = SignOutUrl();
			}
			echo Anchor($Text, $Link, 'Entry');
		?></div>
	</div>
</div>

   <div id="Frame">
      <div id="Body">
         <div id="Content"><?php
			/*
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
			*/
			$this->RenderAsset('Content');
			?></div>
         <div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
      </div>
   </div>

<div id="Foot" class="Foot Wrapper">
	<div class="Center">
      <?php
      // echo Anchor('Addons', '/addons');
      // echo Anchor('Contact Us', '/page/contact');
      echo Anchor('Host Your Community With Us', 'http://vanillaforums.com');		
		$this->RenderAsset('Foot');
      ?>
	</div>
</div>
<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>