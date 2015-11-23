<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 * Template Name: Blog page
 */

get_header(); ?>

<?php 
if (!is_single()) {
  global $more;
  $more = 0;
  $tag_name = BLOG_TAG;
  $taxonomy = is_term($tag_name, 'post_tag');
  $term = $taxonomy['term_id'];
  if (!is_numeric($term)) {
    $term = 0;
  }
  $posts_per_page = intval(get_query_var('posts_per_page'));
  $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
  if (is_page('blog')) {
    $is_blog = true;
    $args=array(
    'showposts'=> $posts_per_page,
    'tag' => $tag_name,
    'paged' => $paged,
    );
    query_posts($args);
    $wp_query->is_archive = true; $wp_query->is_home = false;
  } else if (is_home()) {
    $args=array(
    'showposts'=> $posts_per_page,
    'tag__not_in' => array($term),
    'paged' => $paged,
    );
    query_posts($args);
  }
}
?>
	
    <!-- Page -->
    <div id="post">

 

<?php if (!is_home() || $wp_query->max_num_pages > 1) { ?>
<div class="post_head clearfix">

  <!-- Breadcrumb -->
  <?php valen_breadcrumb(); ?>	
  <!-- [END] Breadcrumb -->
  
  <!-- Pagination -->
  <?php //valen_paginate() ?>	
  <!-- Pagination -->

</div>
<?php } ?>

<?php if (have_posts()) : ?>
  
	<?php while (have_posts()) : the_post(); ?>

		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
		
        
        <div class="entry">
        
        <?php $values = get_post_custom_values("image_value"); if ($values[0] != NULL) { // Show Large Image ?>
        
          <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><img src="<?php echo $values[0]; ?>" alt="" class="auth-thumb" width="75px" height="75px" /></a>
        
      <?php } ?>
        
        <h2 class="postheading">      
        
      
			 <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a> 
			 <span>by</span>
			 <?php  
			  if ($author = get_post_custom_values("author_name_value")) {
			 }else{ ?>
             
             <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><img src="http://vanillaforums.org/blog/wp-content/themes/BigVanillaOrg/images/vanilla_auth_pic.png" class="auth-thumb" height="75px" width="75px"/></a>
             
             <?php };
			 $author_link = get_post_custom_values("author_link_value"); 
			 if ($author[0] != NULL) {
			   if ($author_link[0] != NULL) {
			     echo '<a href="'.$author_link[0].'" rel="external">'.$author[0].'</a>';
			   } else {
			     echo $author[0];
			   }
			 } else { ?>
			   <?php the_author() ?>
			 <?php } ?>
			 <?php if (has_tag(BLOG_TAG)) { comments_popup_link( '0 <em>comments</em>', '1 <em>comment</em>', '% <em>comments</em>', 'comments-link'); } ?>
		  </h2>
          
          <p class="postmetadata"><?php echo custom_the_tags(); ?> on <?php the_time('M jS, Y') ?>   <?php comments_popup_link('No Comments', '1 Comment', '% Comments'); ?>&nbsp;&nbsp;<?php edit_post_link('Edit', '', ''); ?></p>
        
      
		    <?php //the_excerpt('Read More'); ?>
            
               <!--<a href="<?php the_permalink() ?>" class="more-link"><span>More</span></a>-->
            <!--<div id="right" class="Menu"><a href="<?php the_permalink() ?>"><span>Read More</span></a></div>-->
		  
          </div>
          
		  <br class="clear" />

		</div>
		

	<?php endwhile; ?>
  

<?php endif; ?>

<div class="post_foot clearfix">
    
  <!-- Pagination -->
  <?php valen_paginate() ?>	
  <!-- Pagination -->

</div>

<?php wp_reset_query(); ?>

</div>
    <!-- [END] Page -->
		
		<?php get_sidebar(); ?>

<?php get_footer(); ?>