<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
  <div id="Frame">
	 <div class="Banner Menu">
		<h1><a class="Title" href="<?php echo Url('/'); ?>"><span><?php echo Gdn_Theme::Logo(); ?></span></a></h1>
		<div class="nav">
		  <div class="links">
			 <a href="http://apps.facebook.com/causes/">Home</a>
			 <a href="http://apps.facebook.com/causes/causes">Causes</a>
			 <a href="http://wishes.causes.com/?bws=causes_header">Wishes</a>
			 <a href="http://causes.com/donate">Donate</a>
		  </div>
		  <div class="auth">
			 <span>
			 <?php
			 if (Gdn::Session()->IsValid()) {
				echo Gdn_Theme::Link('signinout', 'Sign Out', '<a href="%url" class="%class">%text</a>');
			 } else {
				echo Gdn_Theme::Link('signinout', 'Sign In', '<a href="%url" class="%class">%text</a>');
				/*
				$Link = '';
				try {
				  $FB = Gdn::PluginManager()->GetPluginInstance('FacebookPlugin');
				  $ImgSrc = Asset('/plugins/Facebook/design/facebook-icon.png');
				  $ImgAlt = T('Login with Facebook');
				  $SigninHref = $FB->AuthorizeUri();
				  $PopupSigninHref = $FB->AuthorizeUri('display=popup');
				  $Link = '<a id="FacebookAuth" href="'.$SigninHref.'" class="PopupWindow" title="'.$ImgAlt.'" popupHref="'.$PopupSigninHref.'" popupHeight="326" popupWidth="627">{login_text}</a>';
				  echo str_replace('{login_text}', '<img class="FacebookIcon" src="'.$ImgSrc.'" alt="'.$ImgAlt.'" align="bottom" /> Sign In', $Link);
				} catch (Exception $ex) {
				}
				*/
			 }
			 ?>
			 </span>
		  </div>
		</div>
		<div class="VanillaNav">
		  <ul>
			 <?php
				$Wrap = Wrap('<a href="%url" class="%class">%text</a>', 'li');
				echo Gdn_Theme::Link('dashboard', 'Dashboard', $Wrap);
				echo Gdn_Theme::Link('categories', 'Categories', $Wrap);
				echo Gdn_Theme::Link('discussions', 'Discussions', $Wrap);
				echo Gdn_Theme::Link('activity', 'Activity', $Wrap);
				echo Gdn_Theme::Link('inbox', 'Inbox', $Wrap);
				echo Gdn_Theme::Link('profile', 'Profile', $Wrap);
			 ?>
		  </ul>
		</div>
	 </div>
	 <div id="Body">
		<div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
		<div id="Content"><?php $this->RenderAsset('Content'); ?></div>
	 </div>
	 <div id="Foot">
		<div class="FootWrapper">
		  <div id="vanilla_footer_links">
			 <a href="{vanillaurl}"><span>Discussions by Vanilla</span></a>
			 <?php echo Gdn_Theme::Link('dashboard'); ?>
		  </div>
		  <div id="site_footer_links">
			 <div class="tagline">&copy; Causes 2010. Anyone can change the world.</div>
			 <a href="http://apps.facebook.com/causes/">Home</a>
			 <a href="http://apps.facebook.com/causes/help">Help</a>
			 <a href="/widgets">Widgets</a>
			 <a target="blank" href="http://exchange.causes.com/jobs/">Jobs</a>
			 <a target="blank" href="http://nonprofits.causes.com">Nonprofits</a>
			 <a target="blank" href="http://exchange.causes.com">Causes Exchange</a>
			 <a target="blank" href="http://exchange.causes.com/about/">About</a>
			 <a href="http://causes.com/pages/tos">Terms</a>
			 <a target="blank" href="http://apps.facebook.com/causes/privacy">Privacy</a>
		  </div>
		</div>
	 </div>
  </div>
  <?php $this->RenderAsset('Foot'); ?>
</body>
</html>