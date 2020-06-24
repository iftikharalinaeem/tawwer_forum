<?php
echo '<?xml version="1.0" encoding="utf-8"?>';
$Session = Gdn::session();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->renderAsset('Head'); ?>
   <meta name="google-site-verification" content="T7dDWEaTeqt989RCxDJTfoOkbOADnRWLLJTauXxMHVA" />
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div class="Head">
	<div class="Wrapper">
      <div class="InnerWrapper">
         <div class="Center">
            <h1 class="Logo">
               <?php 
               $Text = 'Discussion Forums Evolved, VanillaForums.com';
               echo anchor($Text, '/', ['title' => $Text]);
               ?>
            </h1>
            <div class="Menus">
               <div class="AccountMenu">
                  <?php
                  if ($Session->isValid()) {
                     echo Gdn_Theme::link('dashboard');
                     echo anchor('Support', '/help', 'Support');
                     // Show account link if user has an account
                     // $Session->User->CountNotifications = 12;
                     if (isset($Session->User->AccountID) && is_numeric($Session->User->AccountID) && $Session->User->AccountID > 0)
                        echo anchor('Account', '/account', 'Account');

                     echo Gdn_Theme::link('profile', 'Profile', '<a href="%url" class="Profile">%text</a>');
                     echo anchor('Sign Out', signOutUrl(), 'SignOut', ['SSL' => TRUE]);
                  } else {
                     echo anchor('Sign In', signInUrl(), 'SignIn', ['SSL' => TRUE]);
                  }
                  ?>
               </div>
               <div class="VFMenu">
                  <?php
                  echo anchor(sprite('SpHome').'Home', '/', 'Home', ['SSL' => FALSE]);
                  echo anchor(sprite('SpPlans').'Plans &amp; Pricing', '/plans', 'Plans', ['SSL' => FALSE]);
                  echo anchor(sprite('SpFeatures').'Features', '/features', 'Features', ['SSL' => FALSE]);
                  echo anchor(sprite('SpResources').'Resources', '/resources', 'Resources', ['SSL' => FALSE]);
                  echo anchor(sprite('SpBlog').'Blog', '/blog', 'Blog', ['SSL' => FALSE]);
                  ?>
               </div>
            </div>
            <?php if (!$Session->isValid()) echo anchor('Sign Up', 'plans', 'GreenButton SignUpButton'); ?>
         </div>
		</div>
	</div>
</div>
<div class="Divider"></div>
<div class="Body">
	<div class="Wrapper">
      <div class="InnerWrapper">
         <div class="Center">
            <div id="Panel"><?php $this->renderAsset('Panel'); ?></div>
            <div id="Content"><?php
            if (in_array(strtolower($this->ControllerName), ['discussionscontroller', 'categoriescontroller'])) {
               echo '<div class="SearchForm">';
               $Form = Gdn::factory('Form');
               echo
                  $Form->open(['action' => url('/search'), 'method' => 'get']),
                  $Form->textBox('Search'),
                  $Form->button('Search', ['Name' => '']),
                  $Form->close()
                  .'</div>';
            }
            $this->renderAsset('Content');
            ?></div>
         </div>
      </div>
   </div>
</div>
<div class="Foot">
	<div class="Wrapper">
		<div class="Center">
         <div class="Columns">
            <div class="Column4">
               <strong>About Us</strong>
               <?php
               echo anchor('Contact Us', '/info/contact', '', ['SSL' => FALSE]);
               echo '<br />'.anchor("We're Hiring!", '/info/hiring', '', ['SSL' => FALSE]);
					echo '<br />'.anchor('Press Releases', 'http://vanillaforums.totemapp.com/');
               echo '<br />'.anchor('Follow us on Facebook', 'http://www.facebook.com/vanillaforums', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Follow us on Twitter', 'http://www.twitter.com/vanilla', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Read Our Blog', '/blog', '', ['SSL' => FALSE]);
               ?>
            </div>
            <div class="Column4">
               <strong>Features</strong>
               <?php
               echo anchor('Features', '/features#toc', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Support Communities', '/resources/customer-support-forums#toc', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Migrate Legacy Forum', '/resources/migrating-legacy-forums#toc', '', ['SSL' => FALSE]);
               // echo '<br />'.anchor('Comments Integration', '/features/blog-comments', '', array('SSL' => FALSE));
               ?>
            </div>
            <div class="Column4">
               <strong>Resources</strong>
               <?php
               echo anchor('Resources', '/resources', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Case Studies', '/resources/penny-arcade#toc', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Professional Services', '/resources/custom-plugins#toc', '', ['SSL' => FALSE]);
               // echo '<br />'.anchor('Free Version', '/free-version', '', array('SSL' => FALSE));
               ?>
            </div>
				<div class="Column4">
					<strong>Legal Stuff</strong>
					<?php
               echo anchor('Privacy Policy', '/info/privacy', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Terms of Service', '/info/termsofservice', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Refund Policy', '/info/refund', '', ['SSL' => FALSE]);
					?>
				</div>
         </div>
         <?php $this->renderAsset('Foot'); ?>
		</div>
	</div>
</div>
<?php $this->fireEvent('AfterBody'); ?>
<?php /*
<script type="text/javascript">
document.write(unescape("%3Cscript src='" + ((document.location.protocol=="https:")?"https://snapabug.appspot.com":"http://www.snapengage.com") + "/snapabug.js' type='text/javascript'%3E%3C/script%3E"));</script><script type="text/javascript">
SnapABug.setButton("http://vanillaforums.com/applications/vfcom/design/images/help-tab.png");
SnapABug.addButton("34737bd0-1d78-43ac-be67-b2769cb5f6ae","0","30%");
</script>
*/ ?>
</body>
</html>
