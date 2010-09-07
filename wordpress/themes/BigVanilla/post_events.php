<?php
/**
 * @package WordPress
 * @subpackage Cullection_Theme
 * Template Name Posts: PostEvents
 */

?>

     <?php include( TEMPLATEPATH . '/marketing_header.php' ); ?>

	
    <!-- Page -->
    <div id="Features" class="Center">

    
  
    
      <div class="Info">
         <h1>On the Road with Vanilla Forums</h1>
         <p>We like to get around spreading the Vanilla word!</p>
      </div>
      
<?php if (have_posts()) : ?>

      	<?php while (have_posts()) : the_post(); ?>
        
			 <?php $values = get_post_custom_values("image_value"); if ($values[0] != NULL) { ?>
                    
                    <img src="<?php echo $values[0]; ?>"  />
                    
              <?php } ?>   
   
   <div class="FeatureSections">
   	<div class="post_events_single">
    		  <?php the_content(); ?>
     </div>       
      	<?php endwhile; ?>
      
      <?php endif; ?>
   
    <span id="back" class="BlueButton"><a href="/events">See all events</a></span>
  </div> 
  
  
    <div class="Info">
         <h2>Get Social with Vanilla Forums</h2>
         <p>Be a part of the Vanilla community and join our social networks.</p>
      </div>

    
   <div class="FeatureSections">
   
   <div>
            <a href="http://www.facebook.com/pages/Vanilla-Forums/109337975772721"><h4 class="blue"><i class="Sprite SpriteFacebook"></i> Facebook</h4></a>
       
    	</div>
         <div>
            <a href="http://twitter.com/vanilla"><h4 class="blue"><i class="Sprite SpriteTwitter"></i> Twitter</h4></a>
           

           
    	</div>
         <div>
            <a href="http://www.flickr.com/photos/vanillaforums/"><h4 class="blue"><i class="Sprite SpriteFlickr"></i> Flickr</h4></a>
              
           
               
         </div>

   
   </div>
   
    <div class="Info">
         <h2>The Vanilla Showcase</h2>
         <p>Check out what others have done with their Vanilla.</p>
      </div>

    
   <div class="FeatureSections">
   
   <a href="http://vanillashowcase.com">
   <img src="<?php echo bloginfo('template_url'); ?>/images/vanillashowcase.png" alt="vanilla showcase" title="vanilla showcase" border="0" />
</a>
   
   </div>
    
    </div>
    <!-- [END] Page -->
		

 <?php include( TEMPLATEPATH . '/marketing_footer.php' ); ?>