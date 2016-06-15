<?php if (!defined('APPLICATION')) exit; ?>
<?php Gdn_Theme::assetBegin('Help'); ?>
   <h2>Heads up!</h2>
   <p>Here are the ranks that users can achieve on your site.
      You can customize these ranks and even add new ones.
      Here are some tips.
   </p>
   <ol>
      <li>
         You don't want to have too many ranks. We recommend starting with five. You can add more if your community is really large.
      </li>
      <li>
         It's a good idea to have special ranks for moderators and administrators so that your community can easily see who's in charge.
      </li>
      <li>
         Be creative! Try naming your ranks after things that the community talks about.
      </li>
   </ol>
<?php Gdn_Theme::assetEnd(); ?>
<div class="header-block">
   <h1><?php echo t('Ranks'); ?></h1>
   <div class="btn-group">
      <?php
      echo Anchor(sprintf(T('Add %s'), T('Rank')), '/settings/addrank', 'btn btn-primary');
      ?>
   </div>
</div>
<div class="table-wrap">
   <table id="Ranks" class="AltColumns">
      <thead>
         <tr>
            <th class="LevelColumn"><?php echo T('Level'); ?></th>
            <th class="NameColumn"><?php echo T('Name'); ?></th>
            <th class=""><?php echo T('Label'); ?></th>
            <th class="CriteriaColumn"><?php echo T('Criteria'); ?></th>
            <th class="AbilitiesColumn"><?php echo T('Abilites'); ?></th>
            <th class="OptionsColumn"><?php echo T('Options'); ?></th>
         </tr>
      </thead>
      <tbody>
         <?php foreach ($this->Data('Ranks') as $Row): ?>
         <tr id="Rank_<?php echo $Row['RankID']; ?>">
            <td class="LevelColumn"><?php echo $Row['Level']; ?></td>
            <td class="NameColumn"><div class="CellWrap">
               <?php
               echo $Row['Name'];
               ?></div>
            </td>
            <td>
               <?php
               echo $Row['Label'];
               ?>
            </td>
            <td>
               <?php
               echo RankModel::CriteriaString($Row);
               ?>
            </td>
            <td>
               <?php
               echo RankModel::AbilitiesString($Row);
               ?>
            </td>
            <td>
               <div class="btn-group">
               <?php
               echo Anchor(T('Edit'), '/settings/editrank?rankid='.$Row['RankID'], 'btn btn-edit');
               echo Anchor(T('Delete'), '/settings/deleterank?rankid='.$Row['RankID'], 'btn btn-delete Popup');
               ?>
               </div>
            </td>
         </tr>
         <?php endforeach; ?>
      </tbody>
   </table>
</div>
