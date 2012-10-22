<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 */
?>
<!-- sidebar -->
<div id="sidebar">

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

  <?php if( in_category("vanilla-showcase")) { ?>
  <div id="side-box">
   <strong>Suggest a Term:</strong> Can't find a term you're looking for? Let us know and we'll add it.
   <span class="blue"><a href="mailto:support@vanillaforums.com">Suggest Now</a></span>
   </div>
   
   <div id="side-box-blue">
   Check out the <a href="http://vanillaforums.com/blog/vanilla-glossary">Vanilla Glossary</a>.
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
   
   <?php } else { ?>
   
   
  <div class="full_sidebar">
  
   <div id="side-box">
   <strong>IMPORTANT NOTICE:</strong> We only provide email support for plus and premium plan subscribers.
   <span class="blue">See <a href="http://www.vanillaforums.com/plans">Plans &amp; Pricing</a></span>
   </div>
  

 <div id="side-box-blue">
   Check out the <a href="http://vanillaforums.com/blog/vanilla-glossary">Vanilla Glossary</a>.
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
   
   <h3>Freebies</h3>
   <div id="side-box">
   <center>
    <a href="http://vanillaforums.org/blog/vanilla-wallpapers/"><img src="<?php echo bloginfo('template_url'); ?>/images/freebies.png" alt="vanilla freebies" title="vanilla freebies" border="0" /></a>
  	</center>
    <span class="blue">Share your Vanilla pride with <a href="http://vanillaforums.org/blog/vanilla-wallpapers/">logos,buttons and wallpapers!</a> </span>
  </div>
  <h3>Recently Tweeted</h3>
			<!--<p><a class="Twitter" href="http://twitter.com/vanilla">@vanilla</a></p>--> 
  <div id="side-box">
  
   <?php aktt_sidebar_tweets(); ?>
   
   </div>
   
    <h3>Like us on Facebook</h3> 
 
<div id="side-box">  
<div class="textwidget"><script type="text/javascript" src="http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php/en_US"></script><script type="text/javascript">FB.init("3ae2d7af638e5a1690d1d341ebdde25b");</script><fb:fan profile_id="109337975772721" stream="" connections="12" width="250" height="320"></fb:fan>
   
   <!-- <script type="text/javascript" src="http://static.ak.connect.facebook.com/connect.php/en_US"></script><script type="text/javascript">FB.init("69ab60b2596f96562780baf55c5eee6c");</script><fb:fan profile_id="109337975772721" stream="0" connections="10" logobar="1" width="280" ></fb:fan><div style="font-size:8px; padding-left:10px"><a href="http://www.facebook.com/pages/Vanilla-Forums/109337975772721">Vanilla Forums</a> on Facebook</div>-->
</div>
  </div>  
    <h3>Flickr</h3>

    <div id="flickr">

    
    <!--<p><a class="Flickr" href="http://www.flickr.com/groups/vanillaforums/">Join the flickr pool</a></p> -->
    
<script type="text/javascript" src="http://www.flickr.com/badge_code_v2.gne?count=9&amp;display=latest&amp;size=s&amp;layout=x&amp;source=user&amp;user=49912244@N05"></script>


    </div>
   
   <?php } ?>  
  
</div>
<!-- [END] Sidebar -->
    