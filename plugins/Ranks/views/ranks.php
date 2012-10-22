<?php if (!defined('APPLICATION')) exit; ?>
<style>
   .NameColumn {
      width: 175px;
   }
   
   .LevelColumn {
      text-align: center;
      width: 75px;
   }
   
   tbody .NameColumn,
   tbody .LevelColumn {
      font-size: 15px;
      font-weight: bold;
      vertical-align: middle;
   }
   
   tbody .NameColumn img {
      vertical-align: middle;
   }
   
   tbody .AutoDescription {
      font-size: 11px;
      margin: 10px 0 0;
      border-top: solid 1px #ddd;
      padding: 5px 0 0 0;
   }
</style>
<!--<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Introducing Vanilla Reactions and Badges"), 'http://vanillaforums.com/blog/news/introducing-vanilla-reactions-and-badges'), 'li');
   echo '</ul>';
   ?>
</div>-->

<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Info PageInfo">
   <p><b>Heads up!</b> Here are the ranks that users can achieve on your site.
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
</div>
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
            <?php
            echo Anchor(T('Edit'), '/settings/editrank?rankid='.$Row['RankID'], 'SmallButton');
            echo Anchor(T('Delete'), '/settings/deleterank?rankid='.$Row['RankID'], 'SmallButton Popup');
            ?>
         </td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>
<div class="Wrap">
   <?php
   echo Anchor(sprintf(T('Add %s'), T('Rank')), '/settings/addrank', 'SmallButton');
   ?>
</div>