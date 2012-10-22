<?php
/**
 * @package WordPress
 * @subpackage Cullection_Theme
 * Template Name: Welcome
 */

?>

     <?php include( TEMPLATEPATH . '/marketing_header.php' ); ?>

	
    <!-- Page -->
    <div id="Features" class="Center">

    
  
    
      <div class="Info">
         <h1>Welcome to Vanilla Forums</h1>
         <p>Here are a few things to help you get started.</p>
      </div>

    
   <div class="FeatureSections">
   
   <div>
            <h4><i class="Sprite SpriteNews"></i> The Blog</h4>
            <p class="About">
             Find out what's happening in the world of VanillaForums.com and stay in the loop with all the latest news.
              
            </p>
            
           <p class="button">
               <a href="http://www.vanillaforums.com/blog/help" class="BlueButton">Learn More <i class="Sprite SpriteRarr SpriteRarrDown"><span>&rarr;</span></i></a>            </p>
               
           <p class="About">  <strong>Latest News</strong></p>
                <ul>
                    <?php query_posts('category_name=general&showposts=5'); ?>
                            <?php while (have_posts()) : the_post(); ?>
                            <li><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
                            <?php endwhile;?>
                            
                            
                            
                </ul>    
    	</div>
         <div>
            <h4><i class="Sprite SpriteTuts"></i> Tutorials</h4>
            <p class="About">
             Have a look through our help topics and tutorials. To make things easy for you we are always adding new stuff. 
              
            </p>
            
           <p class="button">
               <a href="http://vanillaforums.com/blog/category/general/" class="BlueButton">Learn More <i class="Sprite SpriteRarr SpriteRarrDown"><span>&rarr;</span></i></a>            </p>
               
           <p class="About">  <strong>Popular Tutorials</strong></p>
                <ul>
                    <?php query_posts('category_name=help-tutorials&showposts=5'); ?>
                            <?php while (have_posts()) : the_post(); ?>
                            <li><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
                            <?php endwhile;?>
                            
                            
                            
                </ul>    
    	</div>
         <div>
            <h4><i class="Sprite SpriteHelp"></i> Vanilla Glossary</h4>
            <p class="About">
               Vanilla has its own lingo. The glossary will introduce you to some of the terminulogy used in Vanilla. 
            </p>

            <p class="button">
               <a href="http://www.vanillaforums.com/blog/help" class="BlueButton">Learn More <i class="Sprite SpriteRarr SpriteRarrDown"><span>&rarr;</span></i></a>            </p>
               
            <p class="About">  <strong>Recent Terms</strong></p>
                <ul>
                    <?php query_posts('category_name=vanilla-glossary&showposts=5'); ?>
                            <?php while (have_posts()) : the_post(); ?>
                            <li><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
                            <?php endwhile;?>
                            
                            
                            
                </ul>       
               
         </div>

   
   </div>
   
   <div class="Info">
         <h2>Vanilla Video Tips</h2>
         <p>Here are a few quick video tips to help you get started.</p>
      </div>

    
   <div class="FeatureSections">
   
   		<div class="videoDesc">
            <p>How to upload a custom logo to your Vanilla Forum using the Banner feature.</p>
           
    	</div>
        
         <div class="video">
            <object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0' width='450' height='277'><param name='movie' value='http://screenr.com/Content/assets/screenr_1116090935.swf' /><param name='flashvars' value='i=98814' /><param name='allowFullScreen' value='true' /><embed src='http://screenr.com/Content/assets/screenr_1116090935.swf' flashvars='i=98814' allowFullScreen='true' width='450' height='277' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed></object>
    	</div>
        
        <div class="videoDesc">
            <p>How to use Theme Options in Vanilla. Theme Options can include: color styles, text areas, and images.</p>
           
    	</div>
        
         <div class="video">
           <object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0' width='450' height='277'><param name='movie' value='http://screenr.com/Content/assets/screenr_1116090935.swf' /><param name='flashvars' value='i=98824' /><param name='allowFullScreen' value='true' /><embed src='http://screenr.com/Content/assets/screenr_1116090935.swf' flashvars='i=98824' allowFullScreen='true' width='450' height='277' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed></object>
    	</div>
        
        <div class="videoDesc">
            <p> How to attach files to discussions and comments in Vanilla Forums.</p>
           
    	</div>
        
         <div class="video">
            <object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0' width='450' height='277'><param name='movie' value='http://screenr.com/Content/assets/screenr_1116090935.swf' /><param name='flashvars' value='i=98829' /><param name='allowFullScreen' value='true' /><embed src='http://screenr.com/Content/assets/screenr_1116090935.swf' flashvars='i=98829' allowFullScreen='true' width='450' height='277' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed></object>
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