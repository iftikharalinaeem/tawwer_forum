<?php if (!defined('APPLICATION')) exit(); ?>
<?php echo heading(t('Manage Badges'), t('Add Badge'), '/badge/manage', 'js-modal btn btn-primary')?>
<div class="table-wrap">
    <table id="Badges" class="table-data js-tj">
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
            if (count($this->data('Badges'))) :
                include($this->fetchViewLocation('badges'));
            else :
                echo '<tr><td colspan="' . (checkPermission('Reputation.Badges.Give') ? '7' : '6') . '">' . t('No badges yet.') . '</td></tr>';
            endif;
            ?>
        </tbody>
    </table>
</div>