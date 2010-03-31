<?php if (!defined('APPLICATION')) exit();
$GardenDomain = str_replace(array('http://', '/'), array('', ''), Gdn::Config('Garden.Domain', ''));
$FolderName = basename(PATH_ROOT);
$CustomDomainPurchased = $GardenDomain == $FolderName ? FALSE : TRUE;
$AdRemovalPurchased = Gdn::Config('EnabledPlugins.GoogleAdSense', '') == '' ? TRUE : FALSE;
$CustomCssPurchased = Gdn::Config('Plugins.CustomCSS.Enabled');

function Css($Bool) {
   echo $Bool ? ' Enabled' : '';
}

function __FormatPrice($PerMonth) {
	$Format = $Format = T('PriceFormat', '<div class="Price"><strong>$%.0f</strong> per month <span>$%.2f per day</span></div>');
	$PerDay = $PerMonth / (365 / 12);
	$Result = sprintf($Format, $PerMonth, $PerDay);
					
	return $Result;
}
?>
<h1>Premium & Enterprise Upgrades</h1>

<div class="Info">
	In addition to all of the free features at VanillaForums.com, we offer these premium & enterprise upgrades for enhanced functionality.
	<div class="NewsletterBox">
	<?php
	$Session = Gdn::Session();
	if ($this->Newsletter == '1') {
			echo 'You will be notified by email as new features & upgrades become available. '
			.Anchor('Unsubscribe', '/plugin/upgrades/unsubscribe/'.$Session->TransientKey())
			.'.';
	} else {
		echo Anchor('Notify me as new features & upgrades become available', '/plugin/upgrades/subscribe/'.$Session->TransientKey(), 'Button');
	}
	?>
	</div>
</div>

<div class="Upgrades">
   
   <h3>Premium Upgrades</h3>
   <table class="SelectionGrid">
      <tbody>
         <tr>
            <td class="FirstCol<?php Css($CustomDomainPurchased); ?>">
               <h4>Custom Domain Name <span>Purchased!</span></h4>
               <em><?php
               if ($CustomDomainPurchased)
                  echo "You've set up the custom domain: ".$GardenDomain;
               else
                  echo "Customize your forum's address.";
               ?></em>
					<?php echo __FormatPrice(ArrayValue('customdomain', $this->Data['Prices'])); ?>
               <div class="Buttons">
                  <?php
                  if (!$CustomDomainPurchased) {
                     echo Anchor('Learn More', '/plugin/learnmore/customdomain', 'Popdown Button');
                     echo Anchor('Buy Now', '/plugin/learnmore/customdomain', 'Popdown Button');
                  } else {
                     echo Anchor('Remove Upgrade', '/plugin/remove/customdomain', 'Popdown Button');
                  }
                  ?>
               </div>
            </td>
            <td class="MiddleCol<?php Css($AdRemovalPurchased); ?>">
               <h4>Ad Removal <span>Purchased!</span></h4>
               <em>Get those ugly advertisements off your forum.</em>
               <?php echo __FormatPrice(ArrayValue('adremoval', $this->Data['Prices'])); ?>
               <div class="Buttons">
                  <?php
                  if (!$AdRemovalPurchased) {
                     echo Anchor('Learn More', '/plugin/learnmore/adremoval', 'Popdown Button');
                     echo Anchor('Buy Now', '/plugin/buynow/adremoval', 'Button');
                  } else {
                     echo Anchor('Remove Upgrade', '/plugin/remove/adremoval', 'Popdown Button');
                  }
                  ?>
               </div>
            </td>
            <td class="LastCol<?php Css($CustomCssPurchased); ?>">
               <h4>Custom CSS <span>Purchased!</span></h4>
               <em>Have complete control over the appearance of your forum.</em>
               <div class="Price">
                  <strong>$6</strong>
                  per month
                  <span>$0.20 per day</span>
               </div>
               <div class="Buttons">
                  <?php
                  if (!$CustomCssPurchased) {
                     echo Anchor('Learn More', '/plugin/learnmore/customcss', 'Popdown Button');
                     echo Anchor('Buy Now', '/plugin/buynow/customcss', 'Button');
                  } else {
                     echo Anchor('Remove Upgrade', '/plugin/remove/customcss', 'Popdown Button');
                  }
                  ?>
               </div>
            </td>
         </tr>
         <tr>
            <td class="FirstCol">
               <h4>File Uploading <span>Purchased!</span></h4>
               <em>Let your users upload files with their discussions.</em>
               <?php
               /*
               <table class="PriceOptions">
                  <tr>
                     <th>1GB</th>
                     <td>$5 <em>per month</em></td>
                  </tr>
                  <tr>
                     <th>2GB</th>
                     <td>$8 <em>per month</em></td>
                  </tr>
                  <tr>
                     <th>5GB</th>
                     <td>$15 <em>per month</em></td>
                  </tr>
               </table>
               */
               ?>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/learnmore/fileuploading', 'Popdown Button'); ?>
                  Coming Soon!
               </div>
            </td>
            <td class="MiddleCol">
               <h4>Secure Login <span>Purchased!</span></h4>
               <em>Use SSL when signing into your <?php echo str_replace(
               array('http://', '/'),
               array('', ''),
               Gdn::Config('Garden.Domain', 'your_forum_name.vanillaforums.com')); ?> domain.</em>
               <?php
               /*
               <div class="Price">
                  <strong>$9</strong>
                  per month
                  <span>$0.30 per day</span>
               </div>
               */
               ?>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/learnmore/ssl', 'Popdown Button'); ?>
                  Coming Soon!
               </div>
            </td>
            <td class="LastCol">
               <h4>Data Transfer</h4>
               <em>Get your existing data into our system, or take it out and put it elsewhere.</em>
               <div class="Price Free">
                  <strong>FREE</strong>
               </div>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/learnmore/datatransfer', 'Popdown Button'); ?>
                  Coming Soon!
               </div>
            </td>
         </tr>
      </tbody>
   </table>

   <h3>Enterprise Upgrades</h3>
   <table class="SelectionGrid">
      <tbody>
         <tr>
            <td class="FirstCol">
               <h4>Extra Bandwidth <span>Purchased!</span></h4>
               <em>For heavy-traffic forums.</em>
               <?php
               /*
               <table class="PriceOptions">
                  <tr>
                     <th>10M</th>
                     <td>$33 <em>per month</em></td>
                  </tr>
                  <tr>
                     <th>100M</th>
                     <td>$99 <em>per month</em></td>
                  </tr>
                  <tr>
                     <th>100M+</th>
                     <td><a href="mailto:support@vanillaforums.com?subject=Extra Bandwidth">Contact Us</a></td>
                  </tr>
               </table>
               */
               ?>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/learnmore/extrabandwidth', 'Popdown Button'); ?>
                  Coming Soon!
               </div>
            </td>
            <td class="MiddleCol">
               <h4>Single Sign-on <span>Purchased!</span></h4>
               <em>Let your existing users access the forum without signing in or registering again.</em>
               <?php
               /*
               <div class="Price">
                  <strong>$49</strong>
                  per month
                  <span>$1.61 per day</span>
               </div>
               */
               ?>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/learnmore/singlesignon', 'Popdown Button'); ?>
                  Coming Soon!
               </div>
            </td>
            <td class="LastCol">
               <h4>Daily Backups <span>Purchased!</span></h4>
               <em>Rest easy knowing that your data is backed up and ready to be restored should the need arise.</em>
               <?php
               /*
               <div class="Price">
                  <strong>$19</strong>
                  per month
                  <span>$0.63 per day</span>
               </div>
               */
               ?>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/learnmore/dailybackups', 'Popdown Button'); ?>
                  Coming Soon!
               </div>
            </td>
         </tr>
      </tbody>
   </table>
</div>
<div>
   <b>Note:</b> All prices are in US dollars. Prices are in Canadian dollars for Canadian residents and are subject to GST and QST.
</div>