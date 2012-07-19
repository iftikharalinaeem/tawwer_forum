<?php
echo '<?xml version="1.0" encoding="utf-8"?>';
$Session = Gdn::Session();
?>
<!DOCTYPE html>
<html>
<head>
   <?php $this->RenderAsset('Head'); ?>
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
               echo Anchor($Text, '/', array('title' => $Text));
               ?>
            </h1>
            <div class="Menus">
               <div class="AccountMenu">
                  <?php
                  if ($Session->IsValid()) {
                     echo Gdn_Theme::Link('dashboard');
                     echo Anchor('Support', '/help', 'Support');
                     // Show account link if user has an account
                     // $Session->User->CountNotifications = 12;
                     if (isset($Session->User->AccountID) && is_numeric($Session->User->AccountID) && $Session->User->AccountID > 0)
                        echo Anchor('Account', '/account', 'Account');

                     echo Gdn_Theme::Link('profile', 'Profile', '<a href="%url" class="Profile">%text</a>');
                     echo Anchor('Sign Out', SignOutUrl(), 'SignOut', array('SSL' => TRUE));
                  } else {
                     echo Anchor('Sign In', SignInUrl(), 'SignIn', array('SSL' => TRUE));
                  }
                  ?>
               </div>
               <div class="VFMenu">
                  <?php
                  echo Anchor(Sprite('SpHome').'Home', '/', 'Home', array('SSL' => FALSE));
                  echo Anchor(Sprite('SpPlans').'Plans &amp; Pricing', '/plans', 'Plans', array('SSL' => FALSE));
                  echo Anchor(Sprite('SpTour').'Tour', '/tour', 'Tour', array('SSL' => FALSE));
                  echo Anchor(Sprite('SpResources').'Resources', '/resources', 'Resources', array('SSL' => FALSE));
                  echo Anchor(Sprite('SpBlog').'Blog', '/blog', 'Blog', array('SSL' => FALSE));
                  ?>
               </div>
            </div>
            <?php if (!$Session->IsValid()) echo Anchor('Sign Up', 'plans', 'GreenButton SignUpButton'); ?>
         </div>
		</div>
	</div>
</div>
<div class="Divider"></div>
<div class="BreadcrumbWrap">
	<div class="Wrapper">
      <div class="InnerWrapper">
         <div class="Row Center">
            <?php echo Gdn_Theme::Breadcrumbs($this->Data('Breadcrumbs')); ?>
         </div>
      </div>
   </div>
</div>
<div class="Body" id="Body">
	<div class="Wrapper">
      <div class="InnerWrapper">
         <div class="Row Center">
            <div id="Panel" class="Column PanelColumn""><?php $this->RenderAsset('Panel'); ?></div>
            <div id="Content" class="Column ContentColumn"><?php
            if (in_array(strtolower($this->ControllerName), array('discussionscontroller', 'categoriescontroller'))) {
               echo '<div class="SearchForm">';
               $Form = Gdn::Factory('Form');
               $Form->InputPrefix = '';
               echo 
                  $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
                  $Form->TextBox('Search'),
                  $Form->Button('Search', array('Name' => '')),
                  $Form->Close()
                  .'</div>';
            }
            $this->RenderAsset('Content');
            ?></div>
         </div>
      </div>
   </div>
</div>
<div class="Foot">
	<div class="Wrapper">
		<div class="Center Row">
         <div class="Columns">
            <div class="Column4">
               <strong>About Us</strong>
               <?php
               echo Anchor('Contact Us', '/info/contact', '', array('SSL' => FALSE));
               echo '<br />'.Anchor("We're Hiring!", '/info/hiring', '', array('SSL' => FALSE));
					echo '<br />'.Anchor('Press Releases', 'http://vanillaforums.totemapp.com/');
               echo '<br />'.Anchor('Follow us on Facebook', 'http://www.facebook.com/vanillaforums', '', array('SSL' => FALSE));
               echo '<br />'.Anchor('Follow us on Twitter', 'http://www.twitter.com/vanilla', '', array('SSL' => FALSE));
               echo '<br />'.Anchor('Read Our Blog', '/blog', '', array('SSL' => FALSE));
               ?>
            </div>
            <div class="Column4">
               <strong>Features</strong>
               <?php
               echo Anchor('Features', '/features#toc', '', array('SSL' => FALSE));
               echo '<br />'.Anchor('Support Communities', '/resources/customer-support-forums#toc', '', array('SSL' => FALSE));
               echo '<br />'.Anchor('Migrate Legacy Forum', '/resources/migrating-legacy-forums#toc', '', array('SSL' => FALSE));
               // echo '<br />'.Anchor('Comments Integration', '/features/blog-comments', '', array('SSL' => FALSE));
               ?>
               <div style="font-family: GoothamRound,'lucida grande','Lucida Sans Unicode',tahoma; font-size: 22px; color: #fff; position: absolute; margin: 20px 0 0;">Sales questions? Call us at: 1-866-845-0815</div>
            </div>
            <div class="Column4">
               <strong>Resources</strong>
               <?php
               echo Anchor('Resources', '/resources', '', array('SSL' => FALSE));
               echo '<br />'.Anchor('Case Studies', '/resources/penny-arcade#toc', '', array('SSL' => FALSE));
               echo '<br />'.Anchor('Professional Services', '/resources/custom-plugins#toc', '', array('SSL' => FALSE));
               // echo '<br />'.Anchor('Free Version', '/free-version', '', array('SSL' => FALSE));
               ?>
            </div>
				<div class="Column4">
					<strong>Legal Stuff</strong>
					<?php
               echo Anchor('Privacy Policy', '/info/privacy', '', array('SSL' => FALSE));
               echo '<br />'.Anchor('Terms of Service', '/info/termsofservice', '', array('SSL' => FALSE));
               echo '<br />'.Anchor('Refund Policy', '/info/refund', '', array('SSL' => FALSE));
					?>
				</div>
         </div>
         <div class="PoweredByRackspace">
            <a href="http://www.rackspace.com" title="Powered By Rackspace"><img src="http://cdn.vni.la/files/powered-by-rackspace-logo-trans.png" /></a>
         </div>
         <?php $this->RenderAsset('Foot'); ?>
		</div>
	</div>
</div>
<?php $this->FireEvent('AfterBody'); ?>
<?php /*
<script type="text/javascript">
document.write(unescape("%3Cscript src='" + ((document.location.protocol=="https:")?"https://snapabug.appspot.com":"http://www.snapengage.com") + "/snapabug.js' type='text/javascript'%3E%3C/script%3E"));</script><script type="text/javascript">
SnapABug.setButton("http://vanillaforums.com/applications/vfcom/design/images/help-tab.png");
SnapABug.addButton("34737bd0-1d78-43ac-be67-b2769cb5f6ae","0","30%");
</script>
*/ ?>
</body>
</html>
