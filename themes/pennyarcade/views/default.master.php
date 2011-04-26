<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php
	$this->RenderAsset('Head');
	$Session = Gdn::Session();
	$Wrap = '<a href="%url" class="%class">%text</a>';
	?>
	<script charset="utf-8" id="gotopage-tpl" type="text/template">
      <div class="GoToPage Hidden">
			<form name="GoToPage" method="get" action="{url}">
			Go To Page: <input type="text" name="Page" value="" />
			</form>
		</div>
	</script>
	<script type="text/javascript">
	// Pager reveal
	$(document).ready(function() {
		// Wrap pagers with a container
		$('.MiniPager').wrap('<div class="PageControl MiniPageControl" />');
		$('.NumberedPager').wrap('<div class="PageControl" />');
		// Add GoToPage Forms to pager containers
		var tpl = $('#gotopage-tpl').html();
		$('.PageControl').each(function() {
			if ($(this).find('a').length > 4) {
				var anchor = $(this).find('.MiniPager a:first, .NumberedPager a:first').get(0);
				$(this).append(tpl.replace('{url}', anchor.href.substr(0, anchor.href.lastIndexOf('/'))));
			}
		});
		goToPage = function(sender) {
			var pageNum = $(sender).find(':input[name="Page"]').val();
			if ((pageNum - 0) == pageNum && pageNum.length > 0)
				document.location = $(sender).attr('action') + '/p' + pageNum;
				
			return false;
		}
		$('.GoToPage form').submit(function() { goToPage(this); return false; });
		$('.GoToPage form :input[name="Page"]').blur(function() { goToPage($(this).parents('form')); });
		$('a.GoToPageLink, .NumberedPager .Next').click(function() {
			$(this).parents('.PageControl').find('.GoToPage').toggle();
			return false;
		});
	});
	</script>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
	<div id="header">
		<div class="pixels">
			<div class="content">
				<h1><a title="Penny Arcade - Forum" href="http://forum.penny-arcade.com/">Penny Arcade Forum</a></h1>
				<ul>
					<li class="nav" id="navComic"><a title="Comic" href="http://www.penny-arcade.com/comic/"><strong>Comic</strong><span style="opacity: 0;"></span></a></li>
					<li class="nav" id="navArchive"><a title="Archive" href="http://www.penny-arcade.com/archive/"><strong>Archive</strong><span style="opacity: 0;"></span></a></li>
					<li class="active nav" id="navForum"><a title="Forum" href="http://forums.penny-arcade.com/"><strong>Forum</strong><span style="opacity: 0;"></span></a></li>
					<li class="nav" id="navStore"><a title="Store" href="http://store.penny-arcade.com/"><strong>Store</strong><span style="opacity: 0;"></span></a></li>
					<li class="nav" id="navPATV"><a title="PATV" href="http://www.penny-arcade.com/patv/"><strong>PATV</strong><span style="opacity: 0;"></span></a></li>
					<li><a title="PA Presents" href="http://www.penny-arcade.com/presents/">PA Presents</a></li>
					<li><a title="PA Scholarship" href="http://www.penny-arcade.com/pas/">PA Scholarship</a></li>
					<li><?php echo Gdn_Theme::Link('signinout'); ?></li>
				</ul>
				<div id="bannerAd">
				</div>
			</div>
		</div>
	</div>
	<div id="Frame">
      <div id="Head">
         <div class="Breadcrumbs">
            <?php echo Gdn_Theme::Link('categories', 'Penny Arcade Forums', $Wrap); ?>
			</div>
			<div class="ProfileMenu">
				<script type="text/javascript">
					$(document).ready(function() {
						$('.TinySearch form :input').blur(function() {
							if ($(this).val() == 'Search Forum')
								return;
							
							if ($(this).val() == '') {
								$(this).val('Search Forum');
								return;
							}
							
							$(this).parents('form').submit();							
						});
						$('.TinySearch form :input').focus(function() {
							if ($(this).val() == 'Search Forum') {
								$(this).val('');
								return;
							}
						});
					});
				</script>
            <div class="TinySearch"><?php
					$Form = Gdn::Factory('Form');
					$Form->InputPrefix = '';
					echo 
						$Form->Open(array('action' => Url('/search'), 'method' => 'get')),
						$Form->TextBox('Search', array('value' => 'Search Forum', 'class' => 'SearchInput')),
						$Form->Close();
				?></div>
				<?php
					// echo Gdn_Theme::Link('activity', 'Activity', $Wrap, array('class' => 'Activity'));
					if ($Session->IsValid()) {
						echo Gdn_Theme::Link('dashboard', 'Dashboard', $Wrap, array('class' => 'Dashboard'));
						echo UserPhoto($Session->User, 'PhotoProfile');
						echo Gdn_Theme::Link('profile', $Session->User->Name, $Wrap, array('class' => 'Profile'));
						$Inbox = '0';
						$CountUnreadConversations = $Session->User->CountUnreadConversations;
						if (is_numeric($CountUnreadConversations) && $CountUnreadConversations > 0)
							$Inbox = $CountUnreadConversations;
						
						echo Anchor('<span class="Alert">'.$Inbox.'</span>', '/messages/all', 'Inbox HasCount');
					} else {
						echo Gdn_Theme::Link('signinout', 'Sign In', $Wrap);
					}
						
				?>
				</ul>
         </div>
      </div>
      <div id="Body">
         <div id="Panel"><?php $this->RenderAsset('Panel'); ?></div>
         <div id="Content"><?php $this->RenderAsset('Content'); ?></div>
      </div>
      <div id="Foot"><?php $this->RenderAsset('Foot'); ?></div>
   </div>
	<div id="footer">
		<div class="content">
			<?php echo Wrap(Anchor(T('Powered by Vanilla'), C('Garden.VanillaUrl')), 'div id="vLogo"'); ?>
			<ul id="brands">
				<li class="btn" id="logoPAX"><a title="PAX" href="http://www.paxsite.com">PAX</a></li>
				<li class="btn" id="logoCP"><a title="Child's Play" href="http://www.childsplaycharity.org">Child's Play</a></li>
				<li class="btn" id="logoPA"><a title="Penny Arcade" href="http://www.penny-arcade.com"><strong>Penny Arcade</strong></a></li>
				<li class="btn" id="logoPATV"><a title="PATV" href="http://www.penny-arcade.com/patv/">PATV</a></li>
				<li class="btn" id="logoFP"><a title="First Party" href="http://www.firstparty.com">First Party</a></li>
			</ul>
		</div>
	</div>	
	<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>
