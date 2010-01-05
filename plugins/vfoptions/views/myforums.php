<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo Gdn::Translate('My Forums'); ?></h1>
<div class="FilterMenu"><?php echo Anchor('Create a New Forum', 'garden/plugin/createforum', 'CreateForum Button Popup'); ?></div>
<div class="Info"><?php echo Gdn::Translate('Below is a list of all of your forums. Each forum is an "island": not sharing upgrades, discussions, or users. Use this screen to manage your forums.'); ?></div>
<table id="Forums" class="AltRows">
   <thead>
      <tr>
         <th>Forum Domain</th>
         <th>Custom Domain</th>
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
               echo Anchor($Site->Name, 'http://'.$Site->Name);
               echo '<strong>↬</strong> Visitors redirected to your custom domain.';
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
               // Offer for them to set up a custom domain
               echo Anchor('Add a custom domain', 'plugins/upgrades', 'Button');
            } else {
               // Otherwise, show the custom domain
               echo '<strong>'.Anchor($Site->Domain, 'http://'.$Site->Domain).'</strong>';
            }
            ?>
         </td>
      </tr>
   <?php
   }
   ?>
   </tbody>
</table>