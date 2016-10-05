<?php if (!defined('APPLICATION')) exit; ?>

<?php Gdn_Theme::assetBegin('Help'); ?>
    <h2>Heads up!</h2>
    <p>Here are the ranks that users can achieve on your site. You can customize these ranks and even add new ones. Here are some tips.</p>
    <ol>
        <li>You don't want to have too many ranks. We recommend starting with five. You can add more if your community is really large.</li>
        <li>It's a good idea to have special ranks for moderators and administrators so that your community can easily see who's in charge.</li>
        <li>Be creative! Try naming your ranks after things that the community talks about.</li>
    </ol>
<?php Gdn_Theme::assetEnd(); ?>
<div class="header-block">
    <h1><?php echo t('Ranks'); ?></h1>
    <div class="btn-group">
        <?php
        echo anchor(sprintf(t('Add %s'), t('Rank')), '/settings/addrank', 'btn btn-primary');
        ?>
    </div>
</div>
<div class="table-wrap">
    <table id="Ranks" class="table-data js-tj">
        <thead>
            <tr>
                <th class="LevelColumn column-xs"><?php echo t('Level'); ?></th>
                <th class="NameColumn column-md" data-tj-main="true"><?php echo t('Name'); ?></th>
                <th class=""><?php echo t('Label'); ?></th>
                <th class="CriteriaColumn column-lg"><?php echo t('Criteria'); ?></th>
                <th class="AbilitiesColumn column-lg"><?php echo t('Abilites', 'Abilities'); ?></th>
                <th class="options column-sm"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->data('Ranks') as $Row): ?>
            <tr id="Rank_<?php echo $Row['RankID']; ?>">
                <td class="LevelColumn"><?php echo $Row['Level']; ?></td>
                <td class="NameColumn">
                    <div class="strong"><?php echo $Row['Name']; ?></div>
                </td>
                <td><?php echo $Row['Label']; ?></td>
                <td><?php echo RankModel::criteriaString($Row); ?></td>
                <td><?php echo RankModel::abilitiesString($Row); ?></td>
                <td class="options">
                    <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('edit'), '/settings/editrank?rankid='.$Row['RankID'], 'btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
                    echo anchor(dashboardSymbol('delete'), '/settings/deleterank?rankid='.$Row['RankID'], 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                    ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
