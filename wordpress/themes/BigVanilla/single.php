<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 */

get_header(); ?>
	
    <!-- Page -->
    
    <div class="post_head clearfix">
      
        <!-- Breadcrumb -->
        <?php valen_breadcrumb(); ?>	
        <!-- [END] Breadcrumb -->
        
        <!-- Pagination -->
        <?php //valen_paginate() ?>	
        <!-- Pagination -->
    
      </div>
      
      
    
    <div id="post" class="inside">
    
      
    
      <?php if (have_posts()) : ?>

      	<?php while (have_posts()) : the_post(); ?>
      
      		<div <?php post_class() ?> id="post-<?php the_ID(); ?>">
      		
      		  
      		  
      		  <?php 
      		  $options = get_post_meta($post->ID, 'new_meta', false);
      		  $download = get_post_meta($post->ID, 'download_link_value', true);
      		  $download_text = get_post_meta($post->ID, 'download_link_text_value', true);
      		  
      		  if ($options || $download || $download_text || function_exists('the_ratings') && !has_tag(BLOG_TAG)) {
      		    
      		    echo '<div class="collection_meta" style="margin-bottom:20px;">';
      		  
        		  if ($options) {
        		    echo '<table class="the_meta">';
        		    foreach ($options as $option) {
        		      $new_value = explode("|", $option);
        		      $type = $new_value[0];
        		      $value = $new_value[1];      
        		      echo '<tr>';
        		        echo '<td>'.$type.'</td>';
        		        echo '<td class="right_td">'.$value.'</td>';
        		      echo '</tr>';
        		    }
        		    echo '</table>';
        		  }
        		  
        		  if (function_exists('the_ratings')  && $post->post_type == 'post') { 
        		    the_ratings(); 
        		  }
        		  
        		  if ($download) {
        		    if (!$download_text) {
        		      $download_text = 'Download';
        		    }
        		    echo '<a href="'.$download.'" class="big_btn"><span>'.$download_text.'</span></a>';
        		  }
        		  echo '<br class="clear" />';
        		  echo '</div>';
      		  
      		  }
      		  
      		  ?>
        		 <?php $values = get_post_custom_values("image_value"); if ($values[0] != NULL) { // Show Large Image ?>
        
          <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><img src="<?php echo $values[0]; ?>" alt="" class="auth-thumb" width="75px" height="75px" /></a>
        
      <?php } ?> 
      			<h2 class="postheading"><?php the_title(); ?> 
      			 <span>by</span> 
      			 <?php  
      			 $author = get_post_custom_values("author_name_value");
      			 $author_link = get_post_custom_values("author_link_value"); 
      			 if ($author[0] != NULL) {
      			   if ($author_link[0] != NULL) {
      			     echo '<a href="'.$author_link[0].'">'.$author[0].'</a>';
      			   } else {
      			     echo $author[0];
      			   }
      			 } else { ?>
      			   <?php the_author() ?>
      			 <?php } ?>
      			</h2>
      			<p class="postmetadata"><?php echo custom_the_tags(); ?> on <?php the_time('M jS, Y') ?></p>
      			<br class="clear" />
      			<div class="entry">
        		  <?php the_content(); ?>
      		  </div>
            
            <br class="clear" />
            
      		</div>
      	
      	<?php comments_template(); ?>	
      
      	<?php endwhile; ?>
      
      <?php endif; ?>
      
      <div class="post_foot clearfix">
        
        <!-- Breadcrumb -->
        <?php //valen_breadcrumb(); ?>	
        <!-- [END] Breadcrumb -->
        
        <!-- Pagination -->
        <?php //valen_paginate() ?>	
        <!-- Pagination -->
      
      </div>
    
    </div>
    <!-- [END] Page -->
		

<?php if( in_category("help-topics") || in_category("help-tutorials") || in_category("template-tags-help-topics") || in_category("vanilla-showcase")) { ?>
      		      <?php include('sidebar_help.php'); ?>


<?php } else { ?>
		<?php get_sidebar(); ?>
        
             <?php } ?>

<?php get_footer(); ?>