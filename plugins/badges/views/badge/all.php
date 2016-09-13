<?php if (!defined('APPLICATION')) exit(); ?>
<div class="header-block">
    <h1><?php echo T('Manage Badges'); ?></h1>
    <div class="btn-group">
        <?php echo anchor(T('Add Badge'), '/badge/manage', 'js-modal btn btn-primary'); ?>
    </div>
</div>
<div class="table-wrap">
    <table id="Badges" class="table-data">
        <thead>
            <tr>
                <th class="BadgeNameHead column-xl"><?php echo T('Badge'); ?></th>
                <th><?php echo T('Class'); ?></th>
                <th class="column-xs"><?php echo T('Level'); ?></th>
                <th class="column-xs"><?php echo T('Given'); ?></th>
                <th class="column-sm"><?php echo T('Active'); ?></th>
                <!--<th><?php echo T('Visible'); ?></th>-->
                <th class="options column-md"><?php echo T('Options'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($this->Data('Badges'))) :
                include($this->FetchViewLocation('badges'));
            else :
                echo '<tr><td colspan="' . (CheckPermission('Reputation.Badges.Give') ? '7' : '6') . '">' . T('No badges yet.') . '</td></tr>';
            endif;
            ?>
        </tbody>
    </table>
</div>
