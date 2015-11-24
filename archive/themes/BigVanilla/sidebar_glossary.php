<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 */
?>
<!-- sidebar -->
<div id="sidebar">

  <div class="full_sidebar">
  
  <!-- Search -->
  <div class="search_wrap">
    <form method="get" id="searchform" action="<?php echo get_option('home'); ?>/" >
      <div>
        <label class="screen-reader-text" for="s">Search for</label>
        <input type="text" value="<?php echo esc_attr(apply_filters('the_search_query', get_search_query())); ?>" name="s" id="s" />
        <input type="submit" id="searchsubmit" value="Search" />
      </div>
    </form>
  </div>
  <!-- [END] Search -->
  
  <br />

   
  <div id="side-box">
   <strong>Suggest a Term:</strong> Can't find a term you're looking for? Let us know and we'll add it.
   <span class="blue"><a href="mailto:support@vanillaforums.com">Suggest Now</a></span>
   </div>

  
  <h3>Tag Cloud</h3> 
  
  <div id="side-box">
  
 <?php if ( function_exists( 'nk_wp_tag_cloud' ) ) {
    echo nk_wp_tag_cloud( 'single=no&separator= &categories=no' );
}; ?>

</div>
  
<h3>Popular Help Topics</h3> 
   
   <div id="side-box">
   	<ul>
   		<!--<?php query_posts('category_name=help-topics&showposts=5'); ?>
				<?php while (have_posts()) : the_post(); ?>
				<li><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
				<?php endwhile;?>-->
                
                <li><a href="http://vanillaforums.com/blog/help-topics/vanilla-template-tags/">Vanilla Template Tags</a></li>
                <li><a href="http://vanillaforums.com/blog/help-topics/custom-theme/">Custom Theme</a></li>
                <li><a href="http://vanillaforums.com/blog/help-topics/vanilla-connect/">Vanilla Connect</a></li>
                <li><a href="http://vanillaforums.com/blog/help-topics/custom-domain-name/">Custom Domain</a></li>
    </ul>    
   
   
   </div>

<h3>Popular Tutorials</h3> 
   
   <div id="side-box">
   	<ul>
   		<?php query_posts('category_name=help-tutorials&showposts=5'); ?>
				<?php while (have_posts()) : the_post(); ?>
				<li><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
				<?php endwhile;?>
                
                
                
    </ul>    
   
   
   </div>
   
   
  
</div>
<!-- [END] Sidebar -->
    