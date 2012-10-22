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
   Cufon.replace('.title', {
      fontFamily: 'Archer',
	  color: '#744000;',
      textShadow: '0px 1px 1px #fff;'
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

  
<div class="Wrap">
  
      
      <div class="Banner">
			<div class="BannerWrapper">
				<h1><a href="http://vanillaforums.org/"><span>Vanilla Forums</span></a></h1>
				<ul>
					<!-- Put your own menu items here -->

					<li class="Home"><a href="http://vanillaforums.org/">Home</a></li>
					
					<li class="Features"><a href="http://vanillaforums.org/features">Features</a></li>
					
					<li class="Addons"><a href="http://vanillaforums.org/addons">Addons</a></li>
					<li class="Community"><a href="http://vanillaforums.org/discussions">Community</a></li>
					<li class="Documentation"><a href="http://vanillaforums.org/docs">Documentation</a></li>
                    <li class="Blog"><a href="http://vanillaforums.org/blog">Blog</a></li>
                    <li class="Services"><a href="http://vanillaforums.org/services">Services</a></li>
					<li class="Download"><a href="http://vanillaforums.org/download">Download</a></li>

					<!-- End Menu Items -->
				</ul>
			</div>
      
   </div>
</div>

<!-- [END] Header -->


<div id="top-bar-wrapper">
<div class="Center">
      <a href="http://www.vanillaforums.org/blog"><h2 class="title">VanillaForums.org Blog</h2></a>
    
   <p>The latest news on VanillaForums.org and the Vanilla community</p>
   </div>
   </div>
   
  



<!-- Content -->
<div id="content_wrap">
  <div id="content" class="clearfix">