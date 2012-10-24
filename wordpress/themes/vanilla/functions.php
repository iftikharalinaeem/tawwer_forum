<?php

//REMOVE ADMIN BAR
remove_action('init', 'wp_admin_bar_init');
add_filter( 'show_admin_bar', '__return_false' );

if ( function_exists('register_sidebar') )
    register_sidebar(array(
		'name' => 'Sidebar',
        'before_widget' => '<div class="block %1$s %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ));
	
if ( function_exists('register_sidebar') )
    register_sidebar(array(
		'name' => 'Blurb',
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => '',
    ));
	
if ( function_exists('register_sidebar') )
    register_sidebar(array(
        'name' => 'Top Navigation',
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => '',
    ));



// post icons //

function get_cat_icon($the_categories) {
   return;
foreach( $the_categories as $category) {

if ($category->cat_name == "News") {
echo '<img src="http://vanillaforums.com/blog/wp-content/themes/CleanVanilla/images/general_ico.png" />';
} elseif ($category->cat_name == "Help") {
echo '<img src="http://vanillaforums.com/blog/wp-content/themes/CleanVanilla/images/help_ico.png" />';
} elseif ($category->cat_name == "Developers") {
echo '<img src="http://vanillaforums.com/blog/wp-content/themes/CleanVanilla/images/dev_ico.png" />';
} elseif ($category->cat_name == "Events") {
echo '<img src="http://vanillaforums.com/blog/wp-content/themes/CleanVanilla/images/events_ico.png" />';
} elseif ($category->cat_name == "Buzz") {
echo '<img src="http://vanillaforums.com/blog/wp-content/themes/CleanVanilla/images/buzz_ico.png" />';
} elseif ($category->cat_name == "Security") {
echo '<img src="http://vanillaforums.com/blog/wp-content/themes/CleanVanilla/images/security_ico.png" />';
} elseif ($category->cat_name == "Video") {
echo '<img src="http://vanillaforums.com/blog/wp-content/themes/CleanVanilla/images/video_ico.png" />';
} else {
echo '<img src="http://vanillaforums.com/blog/wp-content/themes/CleanVanilla/images/vanilla_ico.png" />';
}
}
}

function display_custom($custom_categories) {
foreach( $custom_categories as $category) {

if ($category->cat_name == "Buzz") {
echo '<div class="post postBuzz">';
echo '<div class="PostIcon Buzz">';
} else {
echo '<div class="post">';
echo '<div class="PostIcon">';
} 
}
}



