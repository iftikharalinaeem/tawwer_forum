<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 */

get_header(); ?>
	
    <!-- Page -->
    <div id="page">
    
      <div class="post_head clearfix">

        <!-- Breadcrumb -->
        <?php valen_breadcrumb(); ?>	
        <!-- [END] Breadcrumb -->
    
      </div>
    
    <?php if (have_posts()) : ?>

    	<?php while (have_posts()) : the_post(); ?>
    
    		<div class="post page" id="post-<?php the_ID(); ?>">
    			<h2 class="postheading"><?php the_title(); ?></h2>
    
    			<div class="entry">
    				<?php the_content(); ?>
    			</div>
    
    		</div>
    
    	<?php endwhile; 
    	
    endif; ?>
    
    </div>
    <!-- [END] Page -->
		
		<?php get_sidebar(); ?>

<?php get_footer(); ?>