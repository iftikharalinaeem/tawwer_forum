<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 */

get_header(); ?>
	
    <!-- Page -->
    <div id="post" class="inside">
    
      <?php include('_loop.php'); ?>
    
    </div>
    	
		<?php get_sidebar(); ?>

<?php get_footer(); ?>