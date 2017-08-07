<div class="table-wrap">
    <table id="multisites" class="table-data js-tj">
        <thead>
        <tr>
            <th><?php echo t('Name'); ?></th>
            <th class="column-xl"><?php echo t('Url'); ?></th>
            <th class="column-sm"><?php echo t('Locale'); ?></th>
            <th><?php echo t('Last Sync'); ?></th>
            <th class="column-sm"><?php echo t('Status'); ?></th>
            <th class="column-sm"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->data('Sites') as $Row):
            ?>
            <tr id="<?php echo "Multisite_{$Row['MultisiteID']}"; ?>">
                <td><?php echo htmlspecialchars($Row['Name']); ?></td>
                <td>
                    <?php
                    echo anchor(htmlspecialchars($Row['FullUrl']), $Row['FullUrl'], '', ['target' => '_blank']);
                    ?>
                </td>
                <td>
                    <?php
                    echo htmlspecialchars($Row['Locale']);
                    ?>
                </td>
                <td>
                    <?php
                    echo Gdn_Format::date($Row['DateLastSync'], 'html');
                    ?>
                </td>
                <td class="js-status"><?php echo strtolower($Row['Status']); ?></td>
                <td class="options">
                    <?php
                    echo anchor(dashboardSymbol('delete'), "/multisites/{$Row['MultisiteID']}/delete", 'btn btn-icon js-modal-confirm',
                        ['aria-label' => t('Delete'), 'title' => t('Delete'), 'data-body' => sprintf(t('Are you sure you want to delete this %s?'), t('site'))]);
                    ?>
                </td>
            </tr>
            <?php
        endforeach;
        ?>
        </tbody>
    </table>
</div>

