<?php

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
foreach( $the_categories as $category) {

if ($category->cat_name == "News") {
echo '<img src="http://localhost/wordpress/wp-content/themes/CleanVanilla/images/general_ico.png" />';
} elseif ($category->cat_name == "Help") {
echo '<img src="http://localhost/wordpress/wp-content/themes/CleanVanilla/images/help_ico.png" />';
} elseif ($category->cat_name == "Developers") {
echo '<img src="http://localhost/wordpress/wp-content/themes/CleanVanilla/images/dev_ico.png" />';
} elseif ($category->cat_name == "Events") {
echo '<img src="http://localhost/wordpress/wp-content/themes/CleanVanilla/images/events_ico.png" />';
} elseif ($category->cat_name == "Buzz") {
echo '<img src="http://localhost/wordpress/wp-content/themes/CleanVanilla/images/buzz_ico.png" />';
}
}
}


// custom post type - buzz //

add_action('init', 'buzz_register');
 
function buzz_register() {
 
	$labels = array(
		'name' => _x('Vanilla Buzz', 'post type general name'),
		'singular_name' => _x('Vanilla Buzz Item', 'post type singular name'),
		'add_new' => _x('Add New', 'buzz item'),
		'add_new_item' => __('Add New Vanilla Buzz Item'),
		'edit_item' => __('Edit Vanilla Buzz Item'),
		'new_item' => __('New Vanilla Buzz Item'),
		'view_item' => __('View Vanilla Buzz Item'),
		'search_items' => __('Search Vanilla Buzz'),
		'not_found' =>  __('Nothing found'),
		'not_found_in_trash' => __('Nothing found in Trash'),
		'parent_item_colon' => ''
	);
 
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array('title','editor','thumbnail')
	  ); 
 
	register_post_type( 'buzz' , $args );
	flush_rewrite_rules();
}

register_taxonomy("Buzz", array("buzz"), array("hierarchical" => true, "label" => "Buzz", "singular_label" => "buzz", "rewrite" => true));

// meta data for buzz //
	
add_action("admin_init", "admin_init");
 
function admin_init(){
  add_meta_box("link_meta", "Vanilla Buzz Article Link", "link_article_meta", "buzz", "normal", "low");
}
 

 
function link_article_meta() {
  global $post;
  $custom = get_post_custom($post->ID);
  $link = $custom["link"][0];
  $site = $custom["site"][0];
  $author = $custom["author"][0];
  ?>
  <p><label>Link to Buzz Article:</label><br />
  <textarea cols="50" rows="5" name="link"><?php echo $link; ?></textarea></p>
  <p><label>Buzz Article Site Name:</label><br />
  <textarea cols="50" rows="5" name="site"><?php echo $site; ?></textarea></p>
  <p><label>Author:</label><br />
  <textarea cols="50" rows="5" name="author"><?php echo $author; ?></textarea></p>
  <?php
}

add_action('save_post', 'save_details');

function save_details(){
  global $post;
 
  update_post_meta($post->ID, "link", $_POST["link"]);
  update_post_meta($post->ID, "site", $_POST["site"]);
  update_post_meta($post->ID, "author", $_POST["author"]);
}


// manage columns //

add_action("manage_posts_custom_column",  "buzz_custom_columns");
add_filter("manage_edit-buzz_columns", "buzz_edit_columns");
 
function buzz_edit_columns($columns){
  $columns = array(
    "cb" => "<input type=\"checkbox\" />",
    "title" => "Vanilla Buzz Title",
    "description" => "Description",
  );
 
  return $columns;
}
function buzz_custom_columns($column){
  global $post;
 
  switch ($column) {
    case "description":
      the_excerpt();
      break;
   
    
  }
}

// Query custom post types //

add_filter( 'pre_get_posts', 'my_get_posts' );

function my_get_posts( $query ) {

	if ( is_home() && false == $query->query_vars['suppress_filters'] )
		$query->set( 'post_type', array( 'post', 'buzz' ) );

	return $query;
}

?>