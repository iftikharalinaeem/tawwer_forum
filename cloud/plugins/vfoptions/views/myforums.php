<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo T('My Forums'); ?></h1>
<div class="Info"><?php echo T('Below is a list of all of your forums. Each forum is an "island": not sharing upgrades, discussions, or users. Use this screen to manage your forums.'); ?></div>
<div class="FilterMenu"><?php echo Anchor('Create a New Forum', 'dashboard/plugin/createforum', 'CreateForum SmallButton Popup'); ?></div>
<table id="Forums" class="AltRows">
   <thead>
      <tr>
         <th style="width: 50%">Forum Domain</th>
         <th style="width: 50%">Custom Domain</th>
      </tr>
   </thead>
   <tbody>
   <?php
   $Alt = FALSE;
   foreach ($this->SiteData->Result('Text') as $Site) {
      $Alt = $Alt ? FALSE : TRUE;
      $UsesCustomDomain = $Site->Domain != $Site->Name && $Site->Domain != '';
      ?>
      <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
         <td class="Info nowrap">
            <?php
            if ($UsesCustomDomain) {
               echo $Site->Name
                  .' <span style="font-size: 18px;">↬</span> Visitors will be redirected to your custom domain →';
            } else {
               echo '<strong>'.Anchor($Site->Name, 'http://'.$Site->Name).'</strong>';
            }
            ?>
            <div>
               <?php
               if (!$UsesCustomDomain) {
                  echo Anchor('Rename Domain', '/plugin/renameforum/'.$Site->SiteID.'/'.$Session->TransientKey(), 'RenameSite Popup');
                  ?>
                  <span>|</span>
                  <?php
               }
               echo Anchor('Delete Forum', '/plugin/deleteforum/'.$Site->SiteID.'/'.$Session->TransientKey(), 'DeleteSite Popup'); ?>
            </div>
         </td>
         <td>
            <?php
            if ($UsesCustomDomain) {
               // Otherwise, show the custom domain
               echo '<strong>'.Anchor($Site->Domain, 'http://'.$Site->Domain).'</strong>';
               ?>
               <div>
                  <?php echo Anchor('Remove Custom Domain', 'http://'.$Site->Domain.'/plugin/remove/customdomain/', 'RemoveCustomDomain'); ?>
               </div>
               <?php
            } else {
               // Offer for them to set up a custom domain
               echo Anchor('Add a custom domain', 'http://'.$Site->Name.'/plugin/upgrades', 'Button');
            }
            ?>
         </td>
      </tr>
   <?php
   }
   ?>
   </tbody>
</table>