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
if (checkPermission('Garden.Settings.Manage')) {
   echo heading($this->data('Title'), t('Advanced Settings'), '/reactions/advanced', 'js-modal btn btn-primary');
} else {
   echo heading($this->data('Title'));
}
?>
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

      $reactionBlock = new MediaItemModule(t($ReactionType['Name']), '', $ReactionType['Description']);
      $reactionBlock->setView('media-sm')
         ->addCssClass('image-wrap', 'media-image-wrap-no-border')
         ->setImage('https://badges.v-cdn.net/reactions/50/'.strtolower($ReactionType['UrlCode']).'.png');

      ?>
      <tr id="ReactionType_<?php echo $ReactionType['UrlCode']; ?>" class="<?php echo $State; ?>">
         <td class="NameColumn">
            <?php echo $reactionBlock; ?>
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
