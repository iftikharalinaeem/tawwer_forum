<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 * Template Name: Mark
 */

get_header(); ?>
	
    <!-- Page -->
    <div id="page">
    
     
    <div id="team-pic">   		 

    <img src="<?php echo bloginfo('template_url'); ?>/images/mark.png" alt="mark - team vanilla" title="mark- team vanilla" border="0" />
    
    </div>
    
    <div id="team-bio">
    <h2>Mark</h2>
    <h3>CEO/Founder</h3><br />
    Mark is the creator of Vanilla: a free, open source, standards compliant discussion forum for the web.
<br />
His software has been downloaded and used by millions of people. There is a fantastic community of developers that actively work with and develop for his software.
<br /><br />
<a href="mailto:mark@vanillaforums.com">mark@vanillaforums.com</a>
<br /><br />
<a href="http://vanillaforums.org/profile/Mark">Vanilla Community</a>
<br /><br />
<a href="http://markosullivan.ca/">Blog</a>
    </div>
    
    
    
    </div>
    <!-- [END] Page -->
    
    <div id="team-side">
    <h3>the Vanilla crew</h3>
     <ul>
      <li class="active">Mark</li> 
      <li><a href="/blog/todd">Todd</a></li> 
      <li><a href="/blog/brendan">Brendan</a></li> 
      <li><a href="/blog/tim">Tim</a></li>
     </ul>  
    </div>
		
		<?php //include('sidebar_team.php'); ?>

<?php get_footer(); ?>