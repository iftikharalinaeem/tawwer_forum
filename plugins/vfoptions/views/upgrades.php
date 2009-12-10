<?php if (!defined('APPLICATION')) exit(); ?>
<h1>Premium Upgrades</h1>
<div class="Info">In addition to all of the free features at VanillaForums.com, we also offer these premium upgrades for enhanced functionality.</div>

<div class="Upgrades">
   <table class="SelectionGrid">
      <tbody>
         <tr>
            <td class="FirstCol">
               <h4>Add a Domain</h4>
               <em>Customize your forum's address.</em>
               <div class="Price">
                  <strong>$2</strong>
                  per month
                  <span>$0.06 per day</span>
               </div>
               <div class="Buttons">
                  <?php
                  echo Anchor('Learn More', '/plugin/moreinfo/addadomain', 'Popup Button');
                  echo Anchor('Buy Now', '/plugin/moreinfo/addadomain', 'Popup Button');
                  ?>
               </div>
            </td>
            <td class="MiddleCol">
               <h4>Ad Removal</h4>
               <em>Get those ugly advertisements off your forum.</em>
               <div class="Price">
                  <strong>$5</strong>
                  per month
                  <span>$0.16 per day</span>
               </div>
               <div class="Buttons">
                  <?php
                  echo Anchor('Learn More', '/plugin/moreinfo/adremoval', 'Popup Button');
                  echo Anchor('Buy Now', '/plugin/moreinfo/adremoval', 'Popup Button');
                  ?>
               </div>
            </td>
            <td class="LastCol">
               <h4>Single Sign-on</h4>
               <em>Let your existing users access the forum without signing in or registering again.</em>
               <div class="Price">
                  <strong>$49</strong>
                  per month
                  <span>$1.61 per day</span>
               </div>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/moreinfo/singlesignon', 'Popup Button'); ?>
                  Coming Soon!
               </div>
            </td>
         </tr>
         <tr>
            <td class="FirstCol">
               <h4>Custom CSS</h4>
               <em>Have complete control over the appearance of your forum.</em>
               <div class="Price">
                  <strong>$5</strong>
                  per month
                  <span>$0.16 per day</span>
               </div>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/moreinfo/customcss', 'Popup Button'); ?>
                  Coming Soon!
               </div>
            </td>
            <td class="MiddleCol">
               <h4>File Uploading</h4>
               <em>Let your users upload files with their discussions.</em>
               <div class="PriceOptions">
                  <div>
                     <strong>1GB</strong>
                     $5 <span>per month</span>
                  </div>
                  <div>
                     <strong>2GB</strong>
                     $8 <span>per month</span>
                  </div>
                  <div>
                     <strong>5GB</strong>
                     $15 <span>per month</span>
                  </div>
               </div>
               <div class="Buttons">
                  <?php echo Anchor('Learn More', '/plugin/moreinfo/fileuploading', 'Popup Button'); ?>
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
                  <?php echo Anchor('Learn More', '/plugin/moreinfo/datatransfer', 'Popup Button'); ?>
                  Coming Soon!
               </div>
            </td>
         </tr>
      </tbody>
   </table>
</div>
