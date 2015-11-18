<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 */
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

  <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
  <title><?php bloginfo('name'); ?> <?php wp_title(); ?></title>
  
  <!-- Styles -->
  <link href="<?php bloginfo('stylesheet_url'); ?>" rel="stylesheet" type="text/css" media="screen" />
  
  <!-- Scripts -->
  <script src="<?php bloginfo('template_directory'); ?>/js/jquery.js" type="text/javascript"></script>
<script src="<?php bloginfo('template_directory'); ?>/js/cufon-yui.js" type="text/javascript"></script>



<script src="<?php bloginfo('template_directory'); ?>/js/archer.js" type="text/javascript"></script>
<script src="<?php bloginfo('template_directory'); ?>/js/gothamround.js" type="text/javascript"></script>

<script type="text/javascript">
   Cufon.replace('h2', {
      fontFamily: 'Archer',
	  color: '#014381;',
      textShadow: '0px 1px 1px #86f4fe;'
   });
   Cufon.replace('h3', {
      fontFamily: 'Archer',
	  color: '#014381;',
      textShadow: '0px 1px 1px #86f4fe;'
   });
   
</script>

<script src="<?php bloginfo('template_directory'); ?>/js/tabber.js" type="text/javascript"></script>
  
  <!-- WordPress Related -->
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php bloginfo('rss2_url'); ?>" />
  <link rel="alternate" type="text/xml" title="RSS .92" href="<?php bloginfo('rss_url'); ?>" />
  <link rel="alternate" type="application/atom+xml" title="Atom 0.3" href="<?php bloginfo('atom_url'); ?>" />
  <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
  
  <!--[if IE 6]>
  
  <![endif]-->
  
  <?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
  <?php wp_head(); ?>

</head>

<body>

<!-- Header -->

  
<div id="Head" class="Wrapper">
   <div class="Center">
      <div class="Logo">
         <a href="http://www.vanillaforums.com/blog"><i class="Sprite SpriteLogo"><span>Vanilla Forums</span></i></a>
      </div>
      <div class="Menu">

			<div class="Home"><a href="http://vanillaforums.com"><span><i class="Sprite SpriteHome"></i> Home</span></a></div>
			<div class="Plans"><a href="http://vanillaforums.com/plans"><span><i class="Sprite SpritePlans"></i> Plans &amp; Pricing</span></a></div>
			<div class="Features"><a href="http://vanillaforums.com/features"><span><i class="Sprite SpriteFeatures"></i> Features</span></a></div>
			<div class="SignIn"><a href="https://vanillaforums.com/entry/signin"><span><i class="Sprite SpriteSignIn"></i> Sign In</span></a></div>

      </div>
   </div>
</div>

<!-- [END] Header -->


   
   <div id="top-bar-wrapper">
<div class="Center">
      <a href="http://www.vanillaforums.com/blog">
      <h2>VanillaForums.com Blog - Help &amp; Support</h2></a>
    
   <p>The latest news on VanillaForums.com and helpful tutorials.</p>
   </div>
   </div>
 



<!-- Content -->
<div id="content_wrap">
  <div id="content" class="clearfix">