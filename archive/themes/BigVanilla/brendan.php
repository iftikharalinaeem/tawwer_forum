<?php
/**
 * @package WordPress
 * @subpackage Collection_Theme
 * Template Name: Brendan
 */

get_header(); ?>
	
    <!-- Page -->
    <div id="page">
    
     
    <div id="team-pic">   		 

    <img src="<?php echo bloginfo('template_url'); ?>/images/brendan.png" alt="brendan - team vanilla" title="brendan- team vanilla" border="0" />
    
    </div>
    
    <div id="team-bio">
    <h2>Brendan</h2>
    <h3>Social Media Manager</h3><br />
    Mark is the creator of Vanilla: a free, open source, standards compliant discussion forum for the web.
<br />
His software has been downloaded and used by millions of people. There is a fantastic community of developers that actively work with and develop for his software.
<br /><br />
<a href="mailto:brendan@vanillaforums.com">brendan@vanillaforums.com</a>
<br /><br />
<a href="http://vanillaforums.org/profile/digibomb">Vanilla Community</a>
<br /><br />
<a href="http://dropthedigibomb.com">Blog</a>
    </div>
    
    
    
    </div>
    <!-- [END] Page -->
    
    <div id="team-side">
    <h3>the Vanilla crew</h3>
     <ul>
      <li><a href="/blog/mark">Mark</a></li> 
      <li><a href="/blog/todd">Todd</a></li> 
      <li class="active">Brendan</li> 
      <li><a href="/blog/tim">Tim</a></li>
     </ul>  
    </div>
		
		<?php //include('sidebar_team.php'); ?>

<?php get_footer(); ?>