<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 * Template Name: Tim
 */

get_header(); ?>
	
    <!-- Page -->
    <div id="page">
    
     
    <div id="team-pic">   		 

    <img src="<?php echo bloginfo('template_url'); ?>/images/tim.png" alt="tim - team vanilla" title="tim- team vanilla" border="0" />
    
    </div>
    
    <div id="team-bio">
    <h2>Tim</h2>
    <h3>Senior Developer</h3><br />
    Tim Gunter is a Senior Developer at Vanilla Forums.
<br />
 Tim has been developing web applications both professionally and personally since the age of 16, teaching himself PHP and Javascript along the way. Tim has worked on some massive projects and has dealt with scaling, massive data storage, distributed computing, and other emerging issues on the modern web, experience he brings with him to the Vanilla Team.
<br /><br />
<a href="mailto:tim@vanillaforums.com">tim@vanillaforums.com</a>
<br /><br />
<a href="http://vanillaforums.org/profile/Tim">Vanilla Community</a>

    </div>
    
    
    
    </div>
    <!-- [END] Page -->
    
    <div id="team-side">
    <h3>the Vanilla crew</h3>
     <ul>
      <li><a href="/blog/mark">Mark</a></li> 
      <li><a href="/blog/todd">Todd</a></li> 
      <li><a href="/blog/brendan">Brendan</a></li> 
      <li class="active">Tim</li>
     </ul>  
    </div>
		
		<?php //include('sidebar_team.php'); ?>

<?php get_footer(); ?>