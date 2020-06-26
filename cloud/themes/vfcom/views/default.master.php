<?php
echo '<?xml version="1.0" encoding="utf-8"?>';
$Session = Gdn::session();
?>
<!DOCTYPE html>
<html>
<head>
   <?php $this->renderAsset('Head'); ?>
   <meta name="google-site-verification" content="T7dDWEaTeqt989RCxDJTfoOkbOADnRWLLJTauXxMHVA" />
   <meta name="google-site-verification" content="XNPgCc6RnVDN47M9vJPLpCr0wQDt2eOj1xf6QZsya7g" />
   <?php
   if (class_exists('PocketsPlugin')) {
      echo PocketsPlugin::pocketString('google-analytics', ['track_page' => $this->data('AnalyticsFunnelPage')]);
   }
   ?>
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
<div class="Head">
   <div class="Wrapper">
      <div class="InnerWrapper">
         <div class="Center">
            <h1 class="Logo">
               <?php 
               $Text = 'Community Forums Evolved, VanillaForums.com';
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
                  // echo anchor(Sprite('SpHome').'Home', '/', 'Home', array('SSL' => FALSE));
                  echo anchor(sprite('SpTour').'Tour', '/tour', 'Product Tour', ['SSL' => FALSE]);
                  echo anchor(sprite('SpResources').'Solutions', '/solutions', 'Solutions', ['SSL' => FALSE]);
                  echo anchor(sprite('SpPlans').'Plans &amp; Pricing', '/plans', 'Plans', ['SSL' => FALSE]);
                  echo anchor(sprite('SpBlog').'Blog', '/blog', 'Blog', ['SSL' => FALSE]);
                  // echo anchor(Sprite('SpShowcase').'Showcase', '/showcase', 'Showcase', array('SSL' => FALSE));
                  ?>
               </div>
            </div>
            <?php if (!$Session->isValid()) echo anchor('Sign Up', 'plans', 'GreenButton SignUpButton'); ?>
         </div>
      </div>
   </div>
</div>
<div class="Divider"></div>
<div class="BreadcrumbWrap">
   <div class="Wrapper">
      <div class="InnerWrapper">
         <div class="Row Center">
            <?php echo Gdn_Theme::breadcrumbs($this->data('Breadcrumbs')); ?>
         </div>
      </div>
   </div>
</div>
<div class="Body" id="Body">
   <div class="Wrapper">
      <div class="InnerWrapper">
         <div class="Row Center">
            <div id="Panel" class="Column PanelColumn"">
               <?php $this->addModule('MeModule'); $this->renderAsset('Panel'); ?>
            </div>
            <div id="Content" class="Column ContentColumn"><?php
            if (in_array(strtolower($this->ControllerName), ['discussionscontroller', 'categoriescontroller'])) {
               echo '<div class="SearchForm">';
               $Form = Gdn::factory('Form');
               $Form->InputPrefix = '';
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
      <div class="Center Row">
         <div class="Columns">
            <div class="Column3">
               <?php
               echo anchor('About Us', 'info/aboutus', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Contact Us', '/info/contact', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Resources', '/resources', '', ['SSL' => FALSE]);
               echo '<br />'.anchor("Jobs", '/info/hiring', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Blog', '/blog', '', ['SSL' => FALSE]);
               echo '<br /><a href="https://plus.google.com/114911737178548458245" rel="publisher">Google+</a>';
               ?>
            </div>
            <div class="Column3">
               <?php
               echo anchor('Tour', '/tour', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Solutions', '/solutions', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Service Offerings', '/solutions#services', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Forum Migration', '/solutions#migration', '', ['SSL' => FALSE]);
               ?>
            </div>
            <div class="Column3">
               <?php
               echo anchor('Terms of Service', '/info/termsofservice', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Privacy Policy', '/info/privacy', '', ['SSL' => FALSE]);
               echo '<br />'.anchor('Refund Policy', '/info/refund', '', ['SSL' => FALSE]);
               ?>
            </div>
         </div>
         <div style="font-family: GoothamRound,'lucida grande','Lucida Sans Unicode',tahoma; font-size: 22px; color: #fff; margin: -20px 0 20px 0; text-align: center;">Sales questions? Call us at: 1-866-845-0815</div>
         <div class="PoweredByRackspace">
            <a href="http://www.rackspace.com" title="Powered By Rackspace"><img src="http://cdn.vni.la/files/powered-by-rackspace-logo-trans.png" /></a>
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
<?php $this->renderAsset('Google'); ?>
</body>
</html>
