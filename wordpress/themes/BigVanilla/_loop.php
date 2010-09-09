<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 */
?>


<div class="post_head clearfix">

 
</div>

 <?php $paged = (get_query_var('paged')) ? get_query_var('paged') : 1; ?>
  <?php query_posts("showposts=10&cat=-32,-36&paged=$paged"); ?>
  <?php while (have_posts()) : the_post(); ?>
  




		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
		
        
        
        
        <?php $values = get_post_custom_values("image_value"); if ($values[0] != NULL) { // Show Large Image ?>
        
          <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><img src="<?php echo $values[0]; ?>" alt="" class="auth-thumb" width="75px" height="75px" /></a>
        
      <?php } ?>
        
        <h2 class="postheading">      
        
      
			 <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a> 
			 <span>by</span>
			 <?php  
			 $author = get_post_custom_values("author_name_value");
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
          <br class="clear" />
      		
        <div class="entry">
      
		    <?php the_content('Read More'); ?>
            
               <!--<a href="<?php the_permalink() ?>" class="more-link"><span>More</span></a>-->
            <!--<div id="right" class="Menu"><a href="<?php the_permalink() ?>"><span>Read More</span></a></div>-->
		  
          </div>
          
          
		  <br class="clear" />

		</div>
		

	<?php endwhile; ?>
	



<div class="post_foot clearfix">
    
  <!-- Pagination -->
<?php wp_pagenavi(); ?>
  <!-- Pagination -->

</div>

<?php wp_reset_query(); ?>