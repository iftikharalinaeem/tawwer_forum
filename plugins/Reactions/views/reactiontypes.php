<?php if (!defined('APPLICATION')) exit; ?>
<?php
$desc = '<p>'.t('Here are all of the reactions you can use on your site.').' '
    .t('Which reactions you use really depends on your community, but we recommend keeping a couple of points in mind.')
    .'</p>'
    .'<ol>'
    .'<li>'.t('Don\'t use too many reactions. You don\'t  want to give your users information overload.').'</li>'
    .'<li>'.t('We recommend mostly positive reactions to encourage participation.').'</li>'
    .'</ol>';

helpAsset(t('Heads up!'), $desc);
helpAsset(t('Need More Help?'), anchor(t("Introducing Vanilla Reactions and Badges"), 'https://blog.vanillaforums.com/news/introducing-vanilla-reactions-and-badges/'));
?>
<div class="header-block">
   <h1><?php echo $this->Data('Title'); ?></h1>
   <?php if (CheckPermission('Garden.Settings.Manage')) { ?>
      <div class="Wrap">
         <?php
         echo Anchor(T('Advanced Settings'), '/reactions/advanced', 'js-modal btn btn-primary');
         ?>
      </div>
   <?php } ?>
</div>
<div class="table-wrap">
<table class="table-data js-tj">
   <thead>
      <tr>
         <th class="NameColumn column-lg"><?php echo T('Reaction'); ?></th>
         <th class="column-xl"><?php echo T("Actions and Permissions"); ?></th>
         <th class="options"></th>
      </tr>
   </thead>
   <tbody>
      <?php foreach ($this->Data('ReactionTypes') as $ReactionType): ?>
      <?php
      if (GetValue('Hidden', $ReactionType)) continue;
      $UrlCode = $ReactionType['UrlCode'];
      $State = $ReactionType['Active'] ? 'Active' : 'InActive';
      ?>
      <tr id="ReactionType_<?php echo $ReactionType['UrlCode']; ?>" class="<?php echo $State; ?>">
         <td class="NameColumn">
            <div class="media media-sm">
               <div class="media-left">
                  <div class="media-image-wrap-no-border">
                     <?php
                     echo Img('http://badges.vni.la/reactions/50/'.strtolower($ReactionType['UrlCode']).'.png', array('ReactionImage')), ' ';
                     ?>
                  </div>
               </div>
               <div class="media-body">
                  <div class="media-title strong">
                     <?php echo t($ReactionType['Name']); ?>
                  </div>
                  <div class="media-description">
                     <?php echo $ReactionType['Description']; ?>
                  </div>
               </div>
            </div>
         </td>
         <td class="AutoDescription">
            <?php
            $AutoDescription = implode('</li><li>', AutoDescription($ReactionType));
            if ($AutoDescription)
               echo Wrap('<li>'.$AutoDescription.'</li>', 'ul');
            ?>
         </td>
         <td class="options">
            <div class="btn-group">
               <?php echo anchor(dashboardSymbol('edit'), "/reactions/edit/{$UrlCode}", 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]); ?>
               <?php echo ActivateButton($ReactionType); ?>
            </div>
         </td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>
</div>
