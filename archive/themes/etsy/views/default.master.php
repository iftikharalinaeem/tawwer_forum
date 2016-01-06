<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
	<?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div id="Frame">

<!-- Header -->
<div id="header">
   <!-- Logo -->
   <h1 id="etsy"><a href="/">Etsy</a></h1>
   <!-- Begin site nav. -->
   <div id="meta-group" class="clear">
      <ul id="meta-nav">
         <li class="first" id="meta-cart"><a href="/cartcheckout.php" title="Your shopping cart">Cart <em>0</em></a></li>
         <li class="last"><a href="/favorite_listings.php" title="Your favorites">Favorites</a></li>
      </ul>
      <!-- User Navigation -->
      <ul id="user-nav">
			<?php
				$Session = Gdn::Session();
				$Authenticator = Gdn::Authenticator();
				if ($Session->IsValid()) {
			?>
         <li>
            <span> Hi, <?php echo $Session->User->Name; ?>.</span> <a href="/your_etsy.php" title="Your Etsy">Your Etsy</a>
         </li>
         <li><a href="/convo_main.php" title="Your conversations">Conversations <strong >0</strong></a></li>
         <li><a href="http://help.etsy.com/app/home" title="Help">Help</a></li>
         <li class="last"><?php echo Anchor('Sign Out', str_replace('{Session_TransientKey}', $Session->TransientKey(), $Authenticator->SignOutUrl()), 'Leave'); ?></li>
		<?php } else { ?>
			<li><a href="/your_etsy.php" title="Your Etsy">Your Etsy</a></li>
			<li><a href="http://help.etsy.com/app/home" title="Help">Help</a></li>
			<li><a href="/register.php" title="Register">Register</a></li>
			<li class="last"><?php echo Anchor('Sign In', $Authenticator->SignInUrl()); ?></li>
		<?php } ?>
      </ul>
    </div>
    <div id="navigation-group" class="clear">
      <!-- Main Navigation -->
      <ul id="main-nav">
         <li class="first"><a href="/buy.php" title="Buy handmade">Buy</a></li>
         <li><a href="/how_selling_works.php" title="Sell handmade">Sell</a></li>
         <li><a href="/alchemy/" title="Request">Request</a></li>
         <li><a href="/community.php" title="Participate in our community">Community</a></li>
         <li class="last"><a href="/storque/" title="Check out the latest Etsy news at the Storque">Blog</a></li>
      </ul>
      <!-- Search -->
      <form action="/search.php" id="search-bar" >
         <div class="input-group">
            <input type="text" value="" name="search_query" id="search-query" class="text"><button id="search_submit" type="submit">
	            <span>Search</span>
	         </button>
         </div>
			<input type="hidden" value="forum_title" name="search_type" id="search-type" /> 
			<div id="search-facet">
				<ul class="closed">
					<li class="forum_title selected">Forum Titles</li>
					<li class="forum_thread">Forum Posts</li>
					<li class="handmade">Handmade</li>
					<li class="vintage">Vintage</li>
					<li class="supplies">Supplies</li>
					<li class="all">All Items</li>
					<li class="seller_usernames">Sellers</li>
				</ul>
			</div>
			<link rel="stylesheet" type="text/css" href="/assets/css/autosuggest.css?v=30166670" />
			<script language='javascript' src="/assets/30166670/js/etsy.as.suggestions.etsy.autosuggest.etsy.template.js"></script>
			<script type="text/javascript">jQuery(function(){ jQuery('#search-query').autosuggest('/suggestions_ajax.php', {"container":"#search-suggestions"} , 'Suggestions');});</script>
		</form>
	</div>
</div>
<script>
if (this.jQuery) {
	 (function($){
		  $("#search-facet").searchDropDown();
		  /*@cc_on 
		  $("#etsy, #main-nav, #search-bar").corner();
		  @*/
	 })(jQuery);
}
</script>
		<table class="EtsyInfo">
			<tr>
				<td colspan="2" class="Breadcrumb">
					<a href="index.php">Home</a> &gt; <a href="community.php">Community</a> &gt; <?php
					if (strtolower($this->ControllerName) != 'categoriescontroller') {
						if (property_exists($this, 'Category') && is_object($this->Category)) {
							echo Anchor('Forums', '/'). ' &gt; '.$this->Category->Name;
						} else {
							echo Anchor('Forums', '/');
						}
					} else {
					?>
				Forums</td>
			</tr>
			<tr>
				<td class="SectionTitle">Etsy Forums</td>
				<td class="RightAlign Strong"><a href="forums_user_threads.php">View topics you've posted in</a></td>
			</tr>
			<tr>
				<td class="Help">
					This is where you can ask for help, share ideas about how to make Etsy better and report any bugs you've found while using the site.
					<a href="dosdonts.php#community">Read the forum guidelines here</a>. Contact <a href="mailto:support@etsy.com">support@etsy.com</a> to discuss any private account matters.
				</td>
				<td class="RightAlign Strong">
					<a href="http://www.etsy.com/storque/section/etsyNews/">Visit the Etsy News section of the Storque blog</a>
					<br />
					<br />
					<a href="http://mailinglist.etsy.com/">Sign up for Etsy Emails to keep up<br>with Etsy news and announcements</a>
					<?php
					}
					?>
				</td>
			</tr>
		</table>
	
	<!--
	<div id="Head">
		<div class="Menu">
			<?php $this->RenderAsset('Menu'); ?>
		</div>
	</div>
	-->
	<div id="Body">
		<div id="Content"><?php $this->RenderAsset('Content'); ?></div>
		<div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
	</div>
	<div id="Foot" class="EtsyWrapper">
		<table>
			<tr>
				<td><a href="/about.php">About</a> | <a href="http://press.etsy.com">Press</a> | <a href="http://www.etsy.com/contact.php">Contact</a> | <a href="http://team.etsy.com">Teams</a> | <a href="http://www.etsy.com/jobs/">Jobs</a> | <a href="http://www.etsy.com/shop.php?user_id=5029420">Etsy Store</a> | <a href="http://developer.etsy.com">Developers</a><br/>                            <a href="/terms_of_use.php">Terms of Use</a> | <a href="/privacy_policy.php">Privacy Policy</a> | <a href="/copyright_policy.php">Copyright Policy</a></td>
				<td class="RightAlign">
					&copy; 2009 Etsy Inc.
					<br />Powered by <a href="http://vanillaforums.com">Vanilla Forums</a>
				</td>
			</tr>
		</table>
	</div>
</div>
</body>
</html>
