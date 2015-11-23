<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 * Template Name: Blog page
 */

get_header(); ?>
	
    <!-- Page -->
    <div id="post">
    
      <?php include('_loop.php'); ?>
    
    </div>
    <!-- [END] Page -->
		
		<?php get_sidebar(); ?>

<?php get_footer(); ?>