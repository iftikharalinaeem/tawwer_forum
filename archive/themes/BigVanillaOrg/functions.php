<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 * Custom functions by Derek Herman
 * http://valendesigns.com
 * Last Updated October 09
 */


//custom login logo

function my_custom_login_logo() {
    echo '<style type="text/css">
        h1 a { background-image:url('.get_bloginfo('template_directory').'/images/login_logo.png) !important; }
    </style>';
}

add_action('login_head', 'my_custom_login_logo');

// custom dashboard logo

add_action('admin_head', 'my_custom_logo');

function my_custom_logo() {
   echo '<style type="text/css">
         #header-logo { background-image: url('.get_bloginfo('template_directory').'/images/dash_logo.png) !important; }</style>';
}



// custom gravatar

function my_own_gravatar( $avatar_defaults ) {
    $myavatar = get_bloginfo('template_directory') . '/images/gravatar.png';
    $avatar_defaults[$myavatar] = 'Vanillatar';
    return $avatar_defaults;
}
add_filter( 'avatar_defaults', 'my_own_gravatar' );



define(BLOG_TAG, 'blog');

remove_action('wp_head','wp_generator');

// Left Sidebar
if (function_exists('register_sidebar')) {
  register_sidebar(array(
    'name' => 'Left Sidebar',
    'before_widget' => '<div class="sidebar_widget">',
    'after_widget' => '</div>',
    'before_title' => '<h3>',
    'after_title' => '</h3>',
  ));
}

// Right Sidebar
if (function_exists('register_sidebar')) {
  register_sidebar(array(
    'name' => 'Right Sidebar',
    'before_widget' => '<div class="sidebar_widget">',
    'after_widget' => '</div>',
    'before_title' => '<h3>',
    'after_title' => '</h3>',
  ));
}

// Full Sidebar
if (function_exists('register_sidebar')) {
  register_sidebar(array(
    'name' => 'Full Sidebar',
    'before_widget' => '<div class="sidebar_widget">',
    'after_widget' => '</div>',
    'before_title' => '<h3>',
    'after_title' => '</h3>',
  ));
}

// Pull out Blog tag
function custom_the_tags($id = 0, $taxonomy = 'post_tag', $before = 'tagged ', $sep = ', ', $after = '' ) {
  $terms = get_the_terms( $id, $taxonomy );
  $tax = is_term(BLOG_TAG, 'post_tag');
  $the_id = $tax['term_id'];
  unset($terms[$the_id]);
	if ( is_wp_error( $terms ) )
		return $terms;
	if ( empty( $terms ) )
		return false;
	foreach ( $terms as $term ) {
    if ($term->name != BLOG_TAG) {
  		$link = get_term_link( $term, $taxonomy );
  		if ( is_wp_error( $link ) )
  			return $link;
  		$term_links[] = '<a href="' . $link . '" rel="tag">' . $term->name . '</a>';
    }
	}
	$term_links = apply_filters( "term_links-$taxonomy", $term_links );
	return $before . join( $sep, $term_links ) . $after;
}

// Gets the grandparent of a page
function get_grandparent() {
global $wpdb, $post;
	$parent = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE ID = '".$post->post_parent."'");
	if ($parent == 0) {
    return false;
  } else {
    return $parent;
  }
}

// Gets the great grandparent of a page
function get_great_grandparent() {
global $wpdb, $post;
	$parent = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE ID = '".$post->post_parent."'");
	$grandparent = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE ID = '".$parent."'");
	if ($parent == 0) {
    return false;
  } else {
    return $grandparent;
  }
} 

// Custom Breadcrumb
if (!function_exists('valen_breadcrumb')) {
	function valen_breadcrumb() { 
		global $post;
		if (!is_home()) {
  		$homelink = '<a href="'.get_bloginfo('home').'">Blog Home</a> &nbsp;&#8250;&nbsp; ';
  	  function wiki_get_category_parents($id, $link = FALSE, $separator = ' &nbsp;&#8250;&nbsp; ', $nicename = FALSE){
  			$chain = '';
  			$parent = &get_category($id);
  			if (is_wp_error( $parent )) {
  			   return $parent;
  			}
  			if ( $nicename ) {
  			   $name = $parent->slug;
  			} else {
  			   $name = $parent->cat_name;
  			}
  			if ($parent->parent && ($parent->parent != $parent->term_id)) {
  			   $chain .= get_category_parents($parent->parent, true, $separator, $nicename);
  			}
  			$chain .= $name;
  			return $chain;	
  		}
  	  if (is_single()) {
  			$cats = get_the_category();
  			$cat = $cats[0];
  			$output .= get_category_parents($cat->term_id, true, " &nbsp;&#172;&nbsp; ");
  			//$output .= get_the_title();
  		}
  		if ( is_category() ) {
  			$cat = intval( get_query_var('cat') );
  			$output .= wiki_get_category_parents($cat, false, " &nbsp;&#8250;&nbsp; ");
  		} elseif ( is_tag() ) {
  			$output .= single_cat_title('',false);
  		} elseif (is_date()) { 
  			$output .= single_month_title(' ',false);
  		} elseif (is_author()) { 
  			global $wp_query;
  	    $curauth = $wp_query->get_queried_object();
  			$output .= $curauth->display_name;
  		} elseif (is_search()) {
  			$output .= esc_attr(apply_filters('the_search_query', get_search_query()));
  		} elseif (is_404()) {
  			$output .= 'Error 404';
  		} else {
  			if (!is_single()) {
  		    $grandparent = get_grandparent();
          $greatgrandparent = get_great_grandparent();
          if ($greatgrandparent) {
            $output .= '<a href="'.get_permalink($greatgrandparent).'">'.get_the_title($greatgrandparent).'</a> &nbsp;&#8250;&nbsp; ';
            $output .= '<a href="'.get_permalink($grandparent).'">'.get_the_title($grandparent).'</a> &nbsp;&#8250;&nbsp; ';
            $output .= '<a href="'.get_permalink($post->post_parent).'">'.get_the_title($post->post_parent).'</a> &nbsp;&#8250;&nbsp; ';
          } else if ($grandparent) {
            $output .= '<a href="'.get_permalink($grandparent).'">'.get_the_title($grandparent).'</a> &nbsp;&#8250;&nbsp; ';
            $output .= '<a href="'.get_permalink($post->post_parent).'">'.get_the_title($post->post_parent).'</a> &nbsp;&#8250;&nbsp; ';
          } else if( $post->post_parent ) {
  					$output .= '<a href="'.get_permalink($post->post_parent).'">'.get_the_title($post->post_parent).'</a> &nbsp;&#8250;&nbsp; ';
  				}
  				$output .= get_the_title();
  			}	
  		}
  		echo '<div class="breadcrumb">'.$homelink.$output.'</div>';
		}
	}
}

// Custom Theme Pagination
if (!function_exists('valen_paginate')) {
	function valen_paginate($comment = FALSE) {
	  global $wpdb, $wp_query;
	  
    // Will Paginate Posts
	  $will_paginate_posts = (!is_singular() && $wp_query->max_num_pages > 1) ? TRUE : FALSE;
	  
	  // Will Paginate Comments
	  $will_paginate_comments = ($comment && is_singular() && $wp_query->max_num_comment_pages > 1) ? TRUE : FALSE;
	  
	  // if $will_paginate_posts = TRUE
	  // show archive pagination 
	  if ( $will_paginate_posts ) {
      $request = $wp_query->request;
  		$posts_per_page = intval(get_query_var('posts_per_page'));
  		$paged = intval(get_query_var('paged'));
  		$found_posts = $wp_query->found_posts;
  		$max_num_pages = $wp_query->max_num_pages;
  		$current_post_num = ($paged * $posts_per_page - $posts_per_page) + 1;
  		$current_post_max = ($posts_per_page * $paged);
  		$total_post_max = ($paged * $posts_per_page - $posts_per_page) + $found_posts % $posts_per_page;
  		if(empty($paged) || $paged == 0) {
  			$paged = 1;
  		}
  		// If Pages
  		if($max_num_pages > 1) {
  		  echo '<div class="valen-paginate">';
  		  echo '<em>';
  		  // If first page
  		  if ($paged == 1) {
  		    echo '1 - '.$posts_per_page.' of '.$found_posts.' ';
  		  // If last page
  		  } else if ($max_num_pages == $paged) {
  		    // If remainder == 0 squash bug
  		    if ($found_posts % $posts_per_page == 0) {
  		      echo $current_post_num.' - '.$found_posts.' of '.$found_posts.' ';
  		    } else {
  		      // If last page and only one post
  		      if ($current_post_num == $total_post_max) {
  		        echo 'Last of '.$found_posts.' ';
  		      } else {
  		        echo $current_post_num.' - '.$total_post_max.' of '.$found_posts.' ';
  		      }
  		    }
  		  // Else everthing is normal
  		  } else {
  		    echo $current_post_num.' - '.$current_post_max.' of '.$found_posts.' ';
  		  }
  		  echo '</em>';
  		  // Previous Link
  		  echo ($max_num_pages > 1 && $paged == 1) ? '<span class="prev_btn">Prev '.$posts_per_page.'</span>' : previous_posts_link('Prev '.$posts_per_page);
        // Next Link
  		  echo ($paged > 1 && $max_num_pages == $paged) ? '<span class="next_btn">Next '.$posts_per_page.'</span>' : next_posts_link('Next '.$posts_per_page);
  		  echo '</div>';
  		}
    }
    
    // show the comments pagination
    // if $will_paginate_comments = TRUE
    if ( $will_paginate_comments ) {
      // New Previous Comments Link
      function get_new_previous_comments_link( $label = '' ) {
      	$page = get_query_var('cpage');
      	if ( intval($page) <= 1 )
      		return;
      	$prevpage = intval($page) - 1;
      	if ( empty($label) )
      		$label = __('&laquo; Older Comments');
      	return '<a href="'.esc_url(get_comments_pagenum_link($prevpage)).'" class="prev_btn">'.preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $label).'</a>';
      }
      // New Next Comments Link
      function get_new_next_comments_link( $label = '', $max_page = 0 ) {
      	global $wp_query;
      	$page = get_query_var('cpage');
      	$nextpage = intval($page) + 1;
      	if ( empty($max_page) )
      		$max_page = $wp_query->max_num_comment_pages;
      	if ( empty($max_page) )
      		$max_page = get_comment_pages_count();
      	if ( $nextpage > $max_page )
      		return;
      	if ( empty($label) )
      		$label = __('Newer Comments &raquo;');
      	return '<a href="'.esc_url(get_comments_pagenum_link($nextpage, $max_page)).'" class="next_btn">'.preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $label).'</a>';
      }
      echo '<div class="valen-paginate">';
      echo (get_new_previous_comments_link()) ? get_new_previous_comments_link('Prev Comments') : '<span class="prev_btn">Prev Comments</span>';
      echo (get_new_next_comments_link()) ? get_new_next_comments_link('More Comments') : '<span class="next_btn">More Comments</span>';
      echo '</div>';
	  }
	  
	  // if $comment = FALSE & is_single()
	  // show post previous & next links
	  if ( !$comment && is_single() ) { 
      // Get Adjacent Post
      $prev = get_adjacent_post(false, false, true);
      $next = get_adjacent_post(false, false, false);
      // Echo Previous or Next Link 
      if ($prev || $next) { 
        echo '<div class="valen-paginate">';
        echo ($prev) ? previous_post_link('<a href="'.get_permalink($prev).'" class="prev_btn">Prev Post</a>') : '<span class="prev_btn">Prev Post</span>';
        echo ($next) ? next_post_link('<a href="'.get_permalink($next).'" class="next_btn">Next Post</a>') : '<span class="next_btn">Next Post</span>';
        echo '</div>';
      }
	  }
	  return false;
	}
}

// Function to add CSS class to previous_posts_link()
function previous_posts_link_class() {
  return 'class="prev_btn"';
}

// Function to add CSS class to next_posts_link()
function next_posts_link_class() {
  return 'class="next_btn"';
}

// Filters to add the CSS classes
add_filter('previous_posts_link_attributes','previous_posts_link_class');
add_filter('next_posts_link_attributes','next_posts_link_class');

// Seperate Trackbacks
function valen_pings_callback($comment, $args) {
	$GLOBALS['comment'] = $comment;
?>
		<li class="trackback">
			<div id="comment-<?php comment_ID(); ?>">
      <div class="comment-author vcard">
         <img class="fake_avatar" src="<?php bloginfo('stylesheet_directory'); ?>/images/trackback.gif" />
         <?php printf(__('<cite class="fn">%s</cite> <span class="says">linked here</span>'), get_comment_author_link()) ?>
      </div>
			
      <?php trackbacks_content(); ?>

     </div>
		</li>
		
<?php
  if (!$comment) {
    echo '<li><!-- No Trackbacks --></li>';
  }
}

// Get Trackback Content
if (!function_exists('trackbacks_content')) {
	function trackbacks_content($words = 30, $link_text = 'Read More', $allowed_tags = '') {
		global $post; 
	  $text = strip_tags($post->post_content, $allowed_tags);
	  $text = explode(' ', $text);
	  $tot = count($text);
	  for ( $i=0; $i<$words; $i++ ) : $output .= $text[$i] . ' '; endfor;	  
	  echo '<p>[... ';
	  echo force_balance_tags($output); 
		if ( $i < $tot ) {
			echo ' ...] </p>';
		} else { 
			echo '</p>';
		}  
	}
}

// Create a meta boxes array
$new_collection_meta_boxes =
  array(
    "image" => array(
      "name" => "image",
      "std" => "",
      "title" => "Collection Image",
      "description" => "<b>The Vanilla logo will be added if you don't choose one of the following: </b><br /> To add Brendan pic use http://vanillaforums.org/blog/wp-content/themes/BigVanillaOrg/images/brendan_auth_pic.png <br /> To add Mark pic use http://vanillaforums.org/blog/wp-content/themes/BigVanillaOrg/images/mark_auth_pic.png <br /> To add Todd pic use http://vanillaforums.org/blog/wp-content/themes/BigVanillaOrg/images/todd_auth_pic.png <br /> To add Tim pic use http://vanillaforums.org/blog/wp-content/themes/BigVanillaOrg/images/tim_auth_pic.png"),
    "author_name" => array(
      "name" => "author_name",
      "std" => "",
      "title" => "Author",
      "description" => "Type the name of the Collection author here."),
    "author_link" => array(
      "name" => "author_link",
      "std" => "",
      "title" => "Author URL",
      "description" => "Type the the URL of the author in a http://link.com format."),
    "download_link" => array(
      "name" => "download_link",
      "std" => "",
      "title" => "Download or Web Site URL",
      "description" => "Type the the URL of the file or download page in a http://link.com format."),
    "download_link_text" => array(
      "name" => "download_link_text",
      "std" => "",
      "title" => "Download Link Text",
      "description" => "Type the the text for the collection link. For example \"Download\" or \"Web Site\" will work great.")
  );

// The Actual Meta Box
function new_collection_meta_boxes() {
  global $post, $new_collection_meta_boxes;

  foreach($new_collection_meta_boxes as $meta_box) {
    $meta_box_value = get_post_meta($post->ID, $meta_box['name'].'_value', true);
  
    if($meta_box_value == "")
      $meta_box_value = $meta_box['std'];
      echo'<input type="hidden" name="'.$meta_box['name'].'_noncename" id="'.$meta_box['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
      echo'<h2 style="margin:0;line-height:24px;padding-left:3px;">'.$meta_box['title'].'</h2>';
      echo'<input type="text" name="'.$meta_box['name'].'_value" value="'.$meta_box_value.'" style="width:98%" /><br />';
      echo'<p><label for="'.$meta_box['name'].'_value">'.$meta_box['description'].'</label></p>';
  }
  echo '<h2 style="margin:0;line-height:24px;padding: 15px 0 0 3px;">Adding More Meta</h2><p>To create metadata such as "Format" or "File Size" add the custom field "new_meta" below, where the Value is a description/value pair seperated with a pipe. For example, "Format|PNG" will show as a description of "format" and value of "PNG". You can add as many custom fields with the Name "new_meta" as you like.</p>';
}

// Create meta box
function create_collection_meta_box() {
  global $theme_name;
  if ( function_exists('add_meta_box') ) {
    add_meta_box( 'new-collection-meta-boxes', 'Collection Post Settings - Optional', 'new_collection_meta_boxes', 'post', 'normal', 'high' );
  }
}

// Save meta box
function save_collection_postdata( $post_id ) {
  global $post, $new_collection_meta_boxes;

  foreach($new_collection_meta_boxes as $meta_box) {
    // Verify
    if ( !wp_verify_nonce( $_POST[$meta_box['name'].'_noncename'], plugin_basename(__FILE__) )) {
      return $post_id;
    }

    if ( 'page' == $_POST['post_type'] ) {
      if ( !current_user_can( 'edit_page', $post_id ))
        return $post_id;
    } else {
      if ( !current_user_can( 'edit_post', $post_id ))
        return $post_id;
    }
  
    $data = $_POST[$meta_box['name'].'_value'];
    
    if(get_post_meta($post_id, $meta_box['name'].'_value') == "")
      add_post_meta($post_id, $meta_box['name'].'_value', $data, true);
    elseif($data != get_post_meta($post_id, $meta_box['name'].'_value', true))
      update_post_meta($post_id, $meta_box['name'].'_value', $data);
    elseif($data == "")
      delete_post_meta($post_id, $meta_box['name'].'_value', get_post_meta($post_id, $meta_box['name'].'_value', true));
  }
}

// Meta Box Actions
add_action('admin_menu', 'create_collection_meta_box');
add_action('save_post', 'save_collection_postdata');

// Theme Options hooks and defaults
add_action('admin_menu', 'add_theme_pages');
add_action('wp_head', 'add_stylesheet');
add_option('theme_style', 'dark-gray');
add_option('site_desc_header', 'About This Site');

$set_the_ads = '<ul>
                  <li><a href="http://themeforest.net"><img src="http://envato.s3.amazonaws.com/referrer_adverts/tf_125x125_v5.gif" alt="" /></a></li>
                  <li><a href="http://flashden.net"><img src="http://envato.s3.amazonaws.com/referrer_adverts/ad_125x125_v4.gif" alt="" /></a></li>
                  <li><a href="http://audiojungle.net"><img src="http://envato.s3.amazonaws.com/referrer_adverts/aj_125x125_v5.gif" alt="" /></a></li>
                  <li><a href="http://graphicriver.net"><img src="http://envato.s3.amazonaws.com/referrer_adverts/gr_125x125_v4.gif" alt="" /></a></li>
                </ul>';

add_option('the_ads', $set_the_ads);
      
add_option('site_desc', 'Collection is a Premium WordPress theme that lets you collect and aggregate just about anything. Directories of free stuff are a great way to build traffic and earn cash, and with this theme it\'s easier than ever to get started.  Just grab a copy of WordPress, buy and install a copy of Collection and start collecting links and downloads.');

// Adds the Theme Options to the admin menu
function add_theme_pages() 
{
	add_theme_page(__('Theme Options'), __('Theme Options'), 'edit_themes', basename(__FILE__), 'admin_options');
	add_thickbox();
}

// Creates the Theme Options page	
function admin_options() 
{
	?>
  <div class="wrap" style="padding-bottom: 40px; position:relative;">
  
  	<h2 style='margin-bottom:25px;'>Collection Theme Settings</h2>
  
  	<?php if(!empty($_GET['updated'])) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>Theme Options Updated</p></div>'; } ?>
    <form method="post" action="options.php">
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="theme_style, analytics_key, feedburner_url, feedburner_mail_url, the_ads, site_desc, site_desc_header" />
    <?php wp_nonce_field('update-options'); ?>
    
    <h2 style="margin-bottom:-10px; font-size: 18px; padding-bottom: 0; line-height: 18px;">Theme Picker</h2>
    <p>
      <label for="theme_style">Choose a Theme:</label>
      <select name="theme_style" id="theme_style" value="<?php echo get_option('theme_style'); ?>" style="width: 163px;">
        <option name="dark-gray" value="dark-gray"<?php if(get_option('theme_style') == "dark-gray") { echo ' selected'; } ?>>Dark Gray</option>
        <option name="green" value="green"<?php if(get_option('theme_style') == "green") { echo ' selected'; } ?>>Green</option>
        <option name="orange" value="ornage"<?php if(get_option('theme_style') == "orange") { echo ' selected'; } ?>>Orange</option>
        <option name="blue" value="blue"<?php if(get_option('theme_style') == "blue") { echo ' selected'; } ?>>Blue</option>
        <option name="grape" value="grape"<?php if(get_option('theme_style') == "grape") { echo ' selected'; } ?>>Grape</option>
        <option name="brown" value="brown"<?php if(get_option('theme_style') == "brown") { echo ' selected'; } ?>>Brown</option>
      </select>
    </p>
    
    <h2 style="margin-bottom:-10px; font-size: 18px; padding-bottom: 0; line-height: 18px;">Analytics Setup</h2>
    <p>
      <label for="analytics_key">Numerical Key:</label><br />
      <input name="analytics_key" id="analytics_key" value="<?php echo get_option('analytics_key'); ?>" style="width: 260px;" />
    </p>
    
    <h2 style="margin-bottom:-10px; font-size: 18px; padding-bottom: 0; line-height: 18px;">Feedburner Account</h2>
    <p>
      <label for="feedburner_url">RSS Feed URL:</label><br />
      <input name="feedburner_url" id="feedburner_url" value="<?php echo get_option('feedburner_url'); ?>" style="width: 260px;" />
    </p>
    <p>
      <label for="feedburner_mail_url">RSS Email Updates URL:</label><br />
      <input name="feedburner_mail_url" id="feedburner_mail_url" value="<?php echo get_option('feedburner_mail_url'); ?>" style="width: 260px;" />
    </p>
    
    <h2 style="margin-bottom:-10px; font-size: 18px; padding-bottom: 0; line-height: 18px;">Sidebar Ads</h2>
    <p>
      <label for="site_desc">Ad code:</label><br />
      <textarea name="the_ads" id="the_ads" rows="5" style="width: 570px;"><?php echo stripslashes(get_option('the_ads')); ?></textarea>
    </p>
    
    <h2 style="margin-bottom:-10px; font-size: 18px; padding-bottom: 0; line-height: 18px;">Footer Text</h2>
    <p>
      <label for="site_desc_header">Site Description Heading:</label><br />
      <input name="site_desc_header" id="site_desc_header" value="<?php echo get_option('site_desc_header'); ?>" style="width: 260px;" />
    </p>
    <p>
      <label for="site_desc">Site Desription:</label><br />
      <textarea name="site_desc" id="site_desc" rows="5" style="width: 570px;"><?php echo stripslashes(get_option('site_desc')); ?></textarea>
    </p>
    
    <p class="submit"><input type="submit" value="<?php _e('Save Changes') ?>" name="Submit"/></p>
    </form>
  </div>
  <?php
}

// Adds the theme specific stylesheet
function add_stylesheet() 
{
  echo "\n";
  echo '<!-- Theme Styles -->';
  echo "\n";
	echo '<link rel="stylesheet" href="'. get_bloginfo('template_directory').'/css/'. get_option('theme_style').'.css" type="text/css" media="screen" />';
	echo "\n\n";
}