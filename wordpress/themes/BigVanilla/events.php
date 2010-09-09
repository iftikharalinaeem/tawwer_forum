<?php
/**
 * @package WordPress
 * @subpackage Cullection_Theme
 * Template Name: Events
 */

?>

     <?php include( TEMPLATEPATH . '/marketing_header.php' ); ?>

	
    <!-- Page -->
    <div id="Features" class="Center">

    
  
    
      <div class="Info">
         <h1>On the Road with Vanilla Forums</h1>
         <p>We like to get around spreading the Vanilla word!</p>
      </div>

    
   
           <img src="<?php echo bloginfo('template_url'); ?>/images/events.png" alt="" title="Vanilla Events" border="0" />

   
   
   <div class="FeatureSections">
   
     <div class="post_events_list">
     
     <h4 class="blue"><i class="Sprite SpriteDate"></i> Upcoming Events</h4>
            <p class="About">
             <strong>Find out where Vanilla Forums will be next.</strong>
              
            </p>
			
		<div class="post_events_entry">	
			<?php $paged = (get_query_var('paged')) ? get_query_var('paged') : 1; ?>
      <?php query_posts("showposts=5&cat=36&paged=$paged"); ?>
      <?php while (have_posts()) : the_post(); ?>
      
      <ul><li><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a> </li></ul>
                     
        
        <?php endwhile; ?>

  </div>
  
     </div>
     
     <div class="invite_vanilla">
      <h4 class="blue"><i class="Sprite SpriteEvents"></i> Want More Vanilla Forums?</h4>
            <p class="About">
             <strong>Can't get enough of us?</strong> Have us speak at your event.Tell us where and when and we'll be there!             
            </p>
         
            <a href="mailto:brendan@vanillaforums.com" class="Plans"><strong>Have Us at Your Event</strong> We're really cool, you'll like us!</a>
            
            
            <p class="About">Or, subscribe to our event newsletter.
            <form action="http://vanilla.createsend.com/t/r/s/oiiwu/" method="post" id="subForm">
					<input type="text"  name="cm-oiiwu-oiiwu" id="oiiwu-oiiwu" class="InputBox" />
					<input type="submit" value="Submit" class="BlueButton" />
				</form>

     </div>
   
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
         <p>Check out what others have done with their Vanilla Forums.</p>
      </div>

    
   <div class="FeatureSections">
   
   <a href="http://vanillashowcase.com">
   <img src="<?php echo bloginfo('template_url'); ?>/images/vanillashowcase.png" alt="vanilla showcase" title="vanilla showcase" border="0" />
</a>
   
   </div>
    
    </div>
    <!-- [END] Page -->
		

 <?php include( TEMPLATEPATH . '/marketing_footer.php' ); ?>