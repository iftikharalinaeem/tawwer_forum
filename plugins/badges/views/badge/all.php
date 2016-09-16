<?php if (!defined('APPLICATION')) exit(); ?>
<div class="header-block">
    <h1><?php echo t('Manage Badges'); ?></h1>
    <div class="btn-group">
        <?php echo anchor(t('Add Badge'), '/badge/manage', 'js-modal btn btn-primary'); ?>
    </div>
</div>
<div class="table-wrap">
    <table id="Badges" class="table-data">
        <thead>
            <tr>
                <th class="BadgeNameHead column-xl"><?php echo t('Badge'); ?></th>
                <th><?php echo t('Class'); ?></th>
                <th class="column-xs"><?php echo t('Level'); ?></th>
                <th class="column-xs"><?php echo t('Given'); ?></th>
                <!--<th><?php echo t('Visible'); ?></th>-->
                <th class="options column-md"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($this->Data('Badges'))) :
                include($this->FetchViewLocation('badges'));
            else :
                echo '<tr><td colspan="' . (checkPermission('Reputation.Badges.Give') ? '7' : '6') . '">' . T('No badges yet.') . '</td></tr>';
            endif;
            ?>
        </tbody>
    </table>
</div>
