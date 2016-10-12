<div class="table-wrap">
    <table id="SubcommunityTable" class="Sortable table-data js-tj">
        <thead>
        <tr>
            <th class="column-md"><?php echo t('Name'); ?></th>
            <th><?php echo t('Folder') ?></th>
            <th class="column-sm"><?php echo t('Locale') ?></th>
            <th class="column-sm"></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->data('Sites') as $Row):
            $id = $Row['SubcommunityID'];
            ?>
            <tr data-id="<?php echo $id; ?>" id="<?php echo "Subcommunity_$id"; ?>">
                <td><?php echo htmlspecialchars($Row['Name']); ?></td>
                <td>
                    <?php
                    $url = Gdn::request()->urlDomain('//').Gdn::request()->assetRoot().'/'.htmlspecialchars($Row['Folder']);
                    echo anchor('/'.htmlspecialchars($Row['Folder']), $url);
                    ?>
                </td>
                <td><?php echo strtolower($Row['Locale']); ?></td>
                <td class="options">
                    <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('edit'), "/subcommunities/$id/edit", 'js-modal btn btn-icon',  ['aria-label' => t('Edit'), 'title' => t('Edit')]),
                        anchor(dashboardSymbol('delete'), "/subcommunities/$id/delete", 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete'), 'data-body' => sprintf(t('Are you sure you want to delete this %s?'), t('site'))]);
                    ?>
                    </div>
                </td>
            </tr>
        <?php
        endforeach;
        ?>
        </tbody>
    </table>
</div>
