<?php
/**
 * @package WordPress
 * @subpackage Cullection_Theme
 * Template Name: Knowledge
 */

?>

     <?php include( TEMPLATEPATH . '/marketing_header.php' ); ?>

	
    <!-- Page -->
    <div id="Features" class="Center">

      <div class="Info">
         <h1>Knowledge Center</h1>
         <p>Here are a few things to help you get started.</p>
      </div>

    
   <div class="KnowledgeSections">
   
   <?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>
   		<h4><?php the_title(); ?></h4>
   		
   		<p>
			<?php the_content('Read the rest of this entry &raquo;'); ?>
			</p>
  		
  		
   	
   	<?php endwhile; ?>

   <?php endif; ?>

   </div>
   
       
    </div>
    <!-- [END] Page -->
		

 <?php include( TEMPLATEPATH . '/marketing_footer.php' ); ?>