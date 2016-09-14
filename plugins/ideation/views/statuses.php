<?php if (!defined('APPLICATION')) exit; ?>
<div class="header-block">
    <h1><?php echo $this->data('Title'); ?></h1>
    <div class="btn-group">
        <?php echo anchor(sprintf(t('Add %s'), t('Status')), '/settings/addstatus', 'btn btn-primary js-modal'); ?>
    </div>
</div>
<!--<div class="Info PageInfo">-->
<!--    <p><b>Heads up!</b> Here are the ranks that users can achieve on your site.-->
<!--        You can customize these ranks and even add new ones.-->
<!--        Here are some tips.-->
<!--    </p>-->
<!--    <ol>-->
<!--        <li>-->
<!--            You don't want to have too many ranks. We recommend starting with five. You can add more if your community is really large.-->
<!--        </li>-->
<!--        <li>-->
<!--            It's a good idea to have special ranks for moderators and administrators so that your community can easily see who's in charge.-->
<!--        </li>-->
<!--        <li>-->
<!--            Be creative! Try naming your ranks after things that the community talks about.-->
<!--        </li>-->
<!--    </ol>-->
<!--</div>-->
<div class="table-wrap">
    <table id="statuses" class="table-data">
        <thead>
        <tr>
            <th class="NameColumn column-lg"><?php echo t('Status'); ?></th>
            <th class="IsOpenColumn"><?php echo t('State'); ?></th>
            <th class="options column-sm"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->data('Statuses') as $row): ?>
            <tr id="Status_<?php echo $row['StatusID']; ?>">
                <td class="NameColumn"><div class="CellWrap">
                        <?php
                        echo $row['Name'];
                        echo ($row['IsDefault']) ? '<span class="info default-tag Tag Meta">'.t('Default').'</span>' : '';
                        ?></div>
                </td>
                <td>
                    <?php
                    echo $row['State'];
                    ?>
                </td>
                <td class="options">
                    <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('edit'), '/settings/editstatus/'.$row['StatusID'], 'js-modal btn btn-icon', ['aria-label' => t('Edit')]);
                    echo anchor(dashboardSymbol('delete'), '/settings/deletestatus?statusid='.$row['StatusID'], 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete'), 'data-content' => ['body' => sprintf(t('Are you sure you want to delete this %s?'), t('Status'))]]);
                    ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
