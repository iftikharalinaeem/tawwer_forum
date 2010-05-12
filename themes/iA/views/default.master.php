<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div class="Mask">
	<div class="Mid">
		<div class="Left">
			<div id="Menu">
				<h1><a class="Title" href="<?php echo Url('/'); ?>"><span><?php echo Gdn::Config('Garden.Title', 'Vanilla'); ?></span></a></h1>
				<h3>Main Menu</h3>
				<div class="Links">
					<?php
			      $Session = Gdn::Session();
					$Activities = Wrap(T('Activities'), 'strong');
					$AllDiscussions = Wrap(T('All Discussions'), 'strong');
					$MyBookmarks = Wrap(T('Bookmarked'), 'strong');
					$MyDiscussions = Wrap(T('Mine'), 'strong');
					// $MyDrafts = Wrap(T('My Drafts'), 'strong');
					$Notifications = Wrap(T('Notifications'), 'strong');
					$Inbox = Wrap(T('Inbox'), 'strong');
					$CountBookmarks = 0;
					$CountDiscussions = 0;
					// $CountDrafts = 0;
					$CountConversations = 0;
					$CountUnreadConversations = 0;
					$CountNotifications = 0;
					if ($Session->IsValid()) {
						$CountBookmarks = $Session->User->CountBookmarks;
						$CountDiscussions = $Session->User->CountDiscussions;
						// $CountDrafts = $Session->User->CountDrafts;
						$CountNotifications = $Session->User->CountNotifications;
						$CountUnreadConversations = $Session->User->CountUnreadConversations;
					}
					if (is_numeric($CountBookmarks) && $CountBookmarks > 0)
						$MyBookmarks .= '('.$CountBookmarks.')';
				
					if (is_numeric($CountDiscussions) && $CountDiscussions > 0)
						$MyDiscussions .= '('.$CountDiscussions.')';
				
					// if (is_numeric($CountDrafts) && $CountDrafts > 0)
					// 	$MyDrafts .= '('.$CountDrafts.')';
						
					if (is_numeric($CountNotifications) && $CountNotifications > 0)
						$Notifications .= '('.$CountNotifications.')';
						
					if (is_numeric($CountUnreadConversations) && $CountUnreadConversations > 0)
						$Inbox .= '('.$CountUnreadConversations.')';

					$AllDiscussions = '<i class="Ico AllDiscussions"></i>'.Wrap($AllDiscussions);
					$MyBookmarks = '<i class="Ico Bookmarks"></i>'.Wrap($MyBookmarks);
					$MyDiscussions = '<i class="Ico MyDiscussions"></i>'.Wrap($MyDiscussions);
					// $MyDrafts = '<i class="Ico Drafts"></i>'.Wrap($MyDrafts);
					$Notifications = '<i class="Ico Notifications"></i>'.Wrap($Notifications);
					$Inbox = '<i class="Ico Inbox"></i>'.Wrap($Inbox);
					$Activities = '<i class="Ico Activities"></i>'.Wrap($Activities);


					echo Anchor(Wrap($AllDiscussions), '/discussions', 'Active');
					// if ($CountBookmarks > 0)
						echo Anchor(Wrap($MyBookmarks), '/discussions/bookmarked', 'MyBookmarks');
					
					// if ($CountDiscussions > 0)
						echo Anchor(Wrap($MyDiscussions), '/discussions/mine', 'MyDiscussions');
						
					// if ($CountDrafts > 0)
					// 	echo Anchor(Wrap($MyDrafts), '/drafts', 'MyDrafts');
						
					echo Anchor(Wrap($Activities), '/activity', 'Activities');

					if ($Session->IsValid()) {
						echo Anchor(Wrap($Inbox), '/messages/all', 'Inbox');
						// if ($CountNotifications > 0)
						echo Anchor(Wrap($Notifications), '/notifications', 'Notifications');
					}

					?>
				</div>
				<h3>My Profile</h3>
				<div class="ProfileInfo">
					<?php
					$Authenticator = Gdn::Authenticator();
					if ($Session->IsValid()) {
						$Name = $Session->User->Name;
						echo UserPhoto(UserBuilder($Session->User));
						echo Anchor($Name, '/profile/'.$Session->UserID.'/'.urlencode($Name), 'Username');
						echo Anchor('Settings', '/profile/'.$Session->UserID.'/'.urlencode($Name));
						echo ' | ';
						echo Anchor(T('Sign Out'), $Authenticator->SignOutUrl());
					} else {
						echo Anchor(Wrap(T('Sign In')), $Authenticator->SignInUrl($this->SelfUrl), Gdn::Config('Garden.SignIn.Popup') ? 'SignInPopup' : '');
					}
					?>
				</div>
				<h3>Search</h3>
            <?php
					$Form = Gdn::Factory('Form');
					$Form->InputPrefix = '';
					echo 
						$Form->Open(array('action' => Url('/search'), 'method' => 'get')),
						$Form->TextBox('Search', array('value' => 'What are you looking for?')),
						$Form->Close();
				?>
				<div class="Credit"><?php	
					printf(T('Powered by %s'), '<a href="http://vanillaforums.com"><span>Vanilla Forums</span></a>');
				?></div>
         </div>
			<div id="Content"><?php $this->RenderAsset('Content'); ?></div>
			<div id="SubContent"><?php $this->RenderAsset('SubContent'); ?></div>
      </div>
   </div>
</div>
<div id="Foot">
	<?php $this->RenderAsset('Foot'); ?>
</div>
<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>
