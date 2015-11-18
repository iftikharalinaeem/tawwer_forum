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
  <link href="http://www.vanillaforums.com/applications/vfcom/design/vfcom.css" rel="stylesheet" type="text/css" media="screen" />
  <link href="<?php bloginfo('stylesheet_url'); ?>" rel="stylesheet" type="text/css" media="screen" />
  
  <!-- Scripts -->
  <script src="<?php bloginfo('template_directory'); ?>/js/jquery.js" type="text/javascript"></script>
<script src="<?php bloginfo('template_directory'); ?>/js/cufon-yui.js" type="text/javascript"></script>



<script src="<?php bloginfo('template_directory'); ?>/js/archer.js" type="text/javascript"></script>
<script src="<?php bloginfo('template_directory'); ?>/js/gothamround.js" type="text/javascript"></script>

<script type="text/javascript">
   Cufon.replace('h1', {
      fontFamily: 'GothamRound',
      color: '#fff',
      textShadow: '0px 1px 1px #258bc0;'
   });
   Cufon.replace('h2', {
      fontFamily: 'Archer',
      textShadow: '0px 1px 1px #f2f2f2;'
   });
   Cufon.replace('a.Plans strong, .FeatureSections h4, .FeaturePage h4', {
      fontFamily: 'Archer',
      textShadow: '0px 1px 1px #333333;'
   });
     Cufon.replace('.FeatureSections h4.blue, .FeaturePage h4.blue', {
      fontFamily: 'Archer',
      textShadow: '0px 1px 1px #fff;'
   });
   Cufon.replace('thead th strong, tfoot th strong, a.BuyButton', {
      fontFamily: 'Archer',
	  color: '#fff',
      textShadow: '0px 1px 2px #333'
   });
   Cufon.replace('div.Info p, div.videoDesc p, p.SubSubHead, .Friends p, div.post_events_single h3', {
      fontFamily: 'GothamRound',
	  color: '#003B82',
	  fontSize: '16px',
      textShadow: '0px 1px 1px #fff;'
   });
   Cufon.replace('div.Plan h3', {
      fontFamily: 'Archer'
   });
   Cufon.replace('a.PlanButton', {
      fontFamily: 'Archer',
      textShadow: '0px 1px 2px #333'
   });
   Cufon.replace('div.Card ul li', {
      fontFamily: 'GothamRound',
      textShadow: '0 1px 0 #fff;'
   });
   Cufon.replace('div.Card ul li span', {
      fontFamily: 'GothamRound',
      textShadow: '0 1px 0 #000;'
   });
   Cufon.replace('div.Price em', {
      fontFamily: 'Archer',
      textShadow: '0 1px 0 #fff;'
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

<body class="marketing">

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


   
   
 



<!-- Content -->
<div id="marketing_wrap">
  <div id="content" class="clearfix">