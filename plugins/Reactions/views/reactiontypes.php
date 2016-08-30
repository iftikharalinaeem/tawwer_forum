<?php if (!defined('APPLICATION')) exit; ?>
<?php Gdn_Theme::assetBegin('Help'); ?>
<div class="Help Aside">
   <h2>Heads up!</h2>
   <p>
      Here are all of the reactions you can use on your site.
      Which reactions you use really depends on your community, but we recommend keeping a couple of points in mind.
   </p>
   <ol>
      <li>
         Don't use too many reactions. You don't  want to give your users information overload.
      </li>
      <li>
         We recommend mostly positive reactions to encourage participation.
      </li>
   </ol>
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Introducing Vanilla Reactions and Badges"), 'http://vanillaforums.com/blog/news/introducing-vanilla-reactions-and-badges'), 'li');
   echo '</ul>';
   ?>
</div>
<?php Gdn_Theme::assetEnd(); ?>
<div class="header-block">
   <h1><?php echo $this->Data('Title'); ?></h1>
   <?php if (CheckPermission('Garden.Settings.Manage')) { ?>
      <div class="Wrap">
         <?php
         echo Anchor(T('Advanced Settings'), '/reactions/advanced', 'Button');
         ?>
      </div>
   <?php } ?>
</div>
<div class="table-wrap">
<table id="Badges" class="AltColumns ManageBadges">
   <thead>
      <tr>
         <th class="NameColumn" colspan="2"><?php echo T('Reaction'); ?></th>
         <th><?php echo T("Actions and Permissions"); ?></th>
         <th class="active"><?php echo T('Active'); ?></th>
         <th class="options"><?php echo T('Options'); ?></th>
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
         <td class="NameColumn"><div class="CellWrap">
            <?php
            echo Img('http://badges.vni.la/reactions/50/'.strtolower($ReactionType['UrlCode']).'.png', array('ReactionImage')), ' ';
            ?></div>
         </td>
         <td>
            <div class="title strong">
               <?php echo t($ReactionType['Name']); ?>
            </div>
            <div class="description">
               <?php echo $ReactionType['Description']; ?>
            </div>
         </td>
         <td class="AutoDescription">
            <?php
            $AutoDescription = implode('</li><li>', AutoDescription($ReactionType));
            if ($AutoDescription)
               echo Wrap('<li>'.$AutoDescription.'</li>', 'ul');
            ?>
         </td>
         <td>
            <?php
            echo ActivateButton($ReactionType);
            ?>
         </td>
         <td>
            <?php echo anchor(dashboardSymbol('edit'), "/reactions/edit/{$UrlCode}", 'js-modal btn btn-icon', ['aria-label' => t('Edit')]); ?>
         </td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>
</div>
