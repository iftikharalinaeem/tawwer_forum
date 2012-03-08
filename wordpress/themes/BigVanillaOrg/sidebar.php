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
  
 
  <h3>Categories</h3>
<div id="side-box">
  
  <li class="categories">
	<?php wp_dropdown_categories('show_option_none=Select category'); ?>

<script type="text/javascript"><!--
    var dropdown = document.getElementById("cat");
    function onCatChange() {
		if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
			location.href = "<?php echo get_option('home');
?>/?cat="+dropdown.options[dropdown.selectedIndex].value;
		}
    }
    dropdown.onchange = onCatChange;
--></script>
</li>

  </div>
 <h3>Archives</h3>
<div id="side-box"> 
  <select name="archive-dropdown" onChange='document.location.href=this.options[this.selectedIndex].value;' class="postform">
<option value=""><?php echo attribute_escape(__('Select Month')); ?></option>
<?php wp_get_archives('type=monthly&format=option&show_post_count=1'); ?> </select>
 </div> 

  <div class="full_sidebar">
  

  
  <h3>Follow us on Twitter</h3>
<div id="side-box">
			<!--<p><a class="Twitter" href="http://twitter.com/vanilla">@vanilla</a></p>--> 
  
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
  </div>
  
</div>
<!-- [END] Sidebar -->
    