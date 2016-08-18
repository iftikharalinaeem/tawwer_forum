<div class="table-wrap">
    <table id="multisites" class="AltColumns">
        <thead>
        <tr>
            <th><?php echo T('Name'); ?></th>
            <th><?php echo T('Url'); ?></th>
            <th><?php echo T('Locale'); ?></th>
            <th><?php echo T('Last Sync'); ?></th>
            <th><?php echo T('Status'); ?></th>
            <th><?php echo T('Options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->Data('Sites') as $Row):
            ?>
            <tr id="<?php echo "Multisite_{$Row['MultisiteID']}"; ?>">
                <td><?php echo htmlspecialchars($Row['Name']); ?></td>
                <td>
                    <?php
                    echo Anchor(htmlspecialchars($Row['FullUrl']), $Row['FullUrl'], '', ['target' => '_blank']);
                    ?>
                </td>
                <td>
                    <?php
                    echo htmlspecialchars($Row['Locale']);
                    ?>
                </td>
                <td>
                    <?php
                    echo Gdn_Format::Date($Row['DateLastSync'], 'html');
                    ?>
                </td>
                <td class="js-status"><?php echo strtolower($Row['Status']); ?></td>
                <td>
                    <?php
                    echo anchor(dashboardSymbol('delete'), "/multisites/{$Row['MultisiteID']}/delete", 'btn btn-icon js-modal-confirm js-hijack',
                        ['aria-label' => t('Delete'), 'data-content' => ['body' => sprintf(t('Are you sure you want to delete this %s?'), t('site'))]]);
                    ?>
                </td>
            </tr>
            <?php
        endforeach;
        ?>
        </tbody>
    </table>
</div>

