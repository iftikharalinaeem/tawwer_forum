<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
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
				</ul>
				<div id="bannerAd">
				</div>
			</div>
		</div>
	</div>
	<div id="Frame">
      <div id="Head">
         <div class="Breadcrumbs">
            <?php
					$Session = Gdn::Session();
					$Wrap = '<a href="%url" class="%class">%text</a>';
					echo Gdn_Theme::Link('categories', 'Penny Arcade Forums', $Wrap);
				?>
			</div>
			<div class="ProfileMenu">
				<?php
					echo Gdn_Theme::Link('activity', 'Activity', $Wrap, array('class' => 'Activity'));
					if ($Session->IsValid()) {
						echo Gdn_Theme::Link('dashboard', 'Dashboard', $Wrap, array('class' => 'Dashboard'));
						echo UserPhoto($Session->User, 'PhotoProfile');
						echo Gdn_Theme::Link('profile', $Session->User->Name, $Wrap, array('class' => 'Profile'));
						echo Gdn_Theme::Link('inbox', '<span class="Hidden">Inbox</span>', $Wrap, array('class' => 'Inbox'));
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
      <div id="Foot">
			<?php
				$this->RenderAsset('Foot');
				echo Wrap(Anchor(T('Powered by Vanilla'), C('Garden.VanillaUrl')), 'div');
			?>
		</div>
   </div>
	<div id="footer">
		<div class="content">
			<ul id="brands">
				<li class="btn" id="logoPAX"><a title="PAX" href="http://www.paxsite.com">PAX</a></li>
				<li class="btn" id="logoFP"><a title="First Party" href="http://www.firstparty.com">First Party</a></li>
				<li class="btn" id="logoCP"><a title="Child's Play" href="http://www.childsplaycharity.org">Child's Play</a></li>
				<li class="btn" id="logoPA"><a title="Penny Arcade" href="http://www.penny-arcade.com"><strong>Penny Arcade</strong></a></li>
				<li class="btn" id="logoPATV"><a title="PATV" href="http://www.penny-arcade.com/patv/">PATV</a></li>
				<li class="btn" id="logoGH"><a title="greenhouse" href="http://www.penny-arcade.com/patv/">greenhouse</a></li>
				<li class="btn" id="logoBench"><a title="The Bench" href="http://www.penny-arcade.com/thebench/">The Bench</a></li>
			</ul>
		</div>
	</div>	
	<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>
