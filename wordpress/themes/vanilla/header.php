<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php wp_title(); ?></title>
<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<!--[if IE]><link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/ie.css" type="text/css" media="screen" /><![endif]-->
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
<link rel="shortcut icon" href="http://www.vanillaforums.com/themes/vfcom/design/favicon.png" type="image/x-icon" />
<?php wp_head(); ?>
<script src="http://vanillaforums.com/js/library/jquery.js" type="text/javascript"></script>
</head>
<body>
<div class="Head">
	<div class="Wrapper">
      <div class="InnerWrapper">
         <div class="Center">
            <h1 class="Logo">
               <a title="Discussion Forums Evolved, VanillaForums.com" href="/">Discussion Forums Evolved, VanillaForums.com</a>
            </h1>
            <div class="Menus">
               <div class="AccountMenu">
                  <a href="/entry/signin" class="SignIn">Sign In</a>
               </div>
               <div class="VFMenu">
                  <a href="/" class="Home"><span class="Sprite SpHome"></span>Home</a><a href="/plans" class="Plans"><span class="Sprite SpPlans"></span>Plans &amp; Pricing</a><a href="/tour" class="Tour"><span class="Sprite SpTour"></span>Tour</a><a href="/resources" class="Resources"><span class="Sprite SpResources"></span>Resources</a><a href="/blog" class="Blog"><span class="Sprite SpBlog"></span>Blog</a>               
               </div>
            </div>
            <a href="/plans" class="GreenButton SignUpButton">Sign Up</a>
         </div>
		</div>
	</div>
</div>
<div class="Divider"></div>
<div class="SubNav">
   <div class="Center posts-<?php
      if (is_category() || is_single()) {
         $cat = get_category_by_path(get_query_var('category_name'), false);
         echo $cat->slug;
      } else {
         echo 'all';
      }
      ?>">
      <a class="posts-all" href="http://vanillaforums.com/blog">All Entries</a>
      <a class="posts-news" href="http://vanillaforums.com/blog/category/news">News</a>
      <a class="posts-help" href="http://vanillaforums.com/blog/category/help">Help</a>
      <a class="posts-developers" href="http://vanillaforums.com/blog/category/developers">Developers</a>
      <a class="posts-events" href="http://vanillaforums.com/blog/category/events">Events</a>
      <a class="posts-philosophy" href="http://vanillaforums.com/blog/category/philosophy">Philosophy</a>
      <a class="posts-buzz" href="http://vanillaforums.com/blog/category/buzz">Buzz</a>
   </div>
</div>
<div class="VFWrapper">
   <div id="wrapper">