<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$HostingDomain = strpos(Gdn::Config('Garden.Domain'), 'chochy') > 0 ? '.chochy.com' : '.vanillaforums.com';
?>
<h1><?php echo Gdn::Translate('My Forums'); ?></h1>
<div class="FilterMenu"><?php echo Anchor('Create a New Forum', 'garden/plugin/createforum', 'CreateForum Button Popup'); ?></div>
<div class="Info"><?php echo Gdn::Translate('Below is a list of all of your forums. Each forum is an "island": not sharing upgrades, discussions, or users. Use this screen to manage your forums.'); ?></div>
<table id="Forums" class="AltRows">
   <thead>
      <tr>
         <th>Name</th>
      </tr>
   </thead>
   <tbody>
   <?php
   $Alt = FALSE;
   foreach ($this->SiteData->Result('Text') as $Site) {
      $Domain = $Site->Domain == '' ? $Site->Name.$HostingDomain : $Site->Domain;
      $Alt = $Alt ? FALSE : TRUE;
      ?>
      <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
         <td class="Info nowrap">
            <strong><?php echo $Domain; ?></strong>
            <div>
               <?php echo Anchor('Visit', 'http://'.$Domain); ?>
               <span>|</span>
               <?php echo Anchor('Rename', 'http://'.substr($HostingDomain, 1).'/manage/rename/'.$Site->SiteID.'/'.$Session->TransientKey(), 'RenameSite Popup'); ?>
               <span>|</span>
               <?php echo Anchor('Delete', '/plugin/deleteforum/'.$Site->SiteID.'/'.$Session->TransientKey(), 'DeleteSite Popup'); ?>
            </div>
         </td>
      </tr>
   <?php
   }
   ?>
   </tbody>
</table>