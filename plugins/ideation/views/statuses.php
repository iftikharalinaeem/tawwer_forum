<?php if (!defined('APPLICATION')) exit; ?>
<?php echo heading($this->data('Title'), sprintf(t('Add %s'), t('Status')), '/settings/addstatus', 'btn btn-primary js-modal'); ?>
<div class="table-wrap">
    <table id="statuses" class="table-data js-tj">
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
                    echo anchor(dashboardSymbol('edit'), '/settings/editstatus/'.$row['StatusID'], 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
                    echo anchor(dashboardSymbol('delete'), '/settings/deletestatus?statusid='.$row['StatusID'], 'js-modal-confirm btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete'), 'data-body' => sprintf(t('Are you sure you want to delete this %s?'), t('Status'))]);
                    ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
