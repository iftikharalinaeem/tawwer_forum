<?php if (!defined('APPLICATION')) exit;

$desc = '<p>'.t('Here are the ranks that users can achieve on your site.').'</p>';
$desc .= '<ol>';
$desc .= '<li>'.t('Recommend starting with five ranks.').'</li>';
$desc .= '<li>'.t('Recommend special ranks for admins and mods.').'</li>';
$desc .= '<li>'.t('Be creative! Try naming your ranks after things that the community talks about.').'</li>';
$desc .= '</ol>';

helpAsset(t('Heads Up!'), $desc);
echo heading(t('Ranks'), sprintf(t('Add %s'), t('Rank')), '/settings/addrank');
?>
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
                    echo anchor(dashboardSymbol('delete'), '/settings/deleterank?rankid='.$Row['RankID'], 'js-modal-confirm btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                    ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
