<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
	<?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div id="Frame">
	<div class="EtsyWrapper">
		<div class="Tools">
			<a href="/cart1.php" title="Cart" class="Cart">Cart <span class="Orange">0 items</span></a>
			<?php
				$Session = Gdn::Session();
				$Authenticator = Gdn::Authenticator();
				if ($Session->IsValid()) {
					echo '<span class="Username">'.$Session->User->Name.': </span>';
					echo '<a href="/your_etsy.php">Your Etsy</a> <span>|</span>';
					echo '<a href="http://help.etsy.com">Help</a> <span>|</span>';
					echo Anchor('Sign Out', str_replace('{Session_TransientKey}', $Session->TransientKey(), $Authenticator->SignOutUrl()), 'Leave');
				} else {
					echo Anchor('Register', $Authenticator->RegisterUrl($this->SelfUrl), 'Register').' <span>|</span>';
					echo '<a href="http://help.etsy.com">Help</a> <span>|</span>';
					echo Anchor('Sign In', $Authenticator->SignInUrl($this->SelfUrl), 'SignInPopup');
				}
			?>
		</div>
		<a href="/" class="Etsy" title="Etsy"><img src="<?php echo Asset('themes/etsy/design/logo.gif'); ?>" alt="Etsy" width="154" height="80" /></a>
		<div class="EtsyMenu">
			<a href="/buy.php" title="Buy">Buy</a>
			<a href="/how_selling_works.php" title="Sell">Sell</a>
			<a href="/alchemy/" title="Custom">Custom</a>
			<a href="/community.php" title="Community">Community</a>
			<a href="/storque/" title="Blog">Blog</a>
			<a href="/your_etsy.php" title="Your Etsy" class="YourEtsy">Your Etsy</a>
		</div>
		<div class="EtsyForm">
			<form action="/search.php" method="GET" id="search-bar">
				<select name="search_type" id="search_select" class="select">
					<option value="forum_title">Forum titles</option>
					<option value="forum_thread">Forum posts</option>
					<option value="handmade">Handmade</option>
					<option value="vintage">Vintage</option>
					<option value="supplies">Supplies</option>
					<option value="all">All Items</option>
					<option value="seller_usernames">Sellers</option>
				</select>
				<input type="text" class="text" id="search-query" name="search_query" value=""/>
				<input type="image" id="search_submit" value="Search" src="<?php echo Asset('/themes/etsy/design/button_search.gif'); ?>" alt="search" class="submit"/>
				<p class="advanced-search"><a href="/search_advanced.php" title="Advanced Search">Advanced Search</a></p>
			</form>
		</div>
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
	</div>
	<!--
	<div id="Head">
		<div class="Menu">
			<?php $this->RenderAsset('Menu'); ?>
		</div>
	</div>
	-->
	<div id="Body">
		<div id="Content"><?php $this->RenderAsset('Content'); ?></div>
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
