<?php
PagerModule::write(array('Sender' => $this));
?>
    <table id="SubcommunityTable" class="Sortable AltColumns">
        <thead>
        <tr>
            <th><?php echo t('Name'); ?></th>
            <th><?php echo t('Folder') ?></th>
            <th><?php echo t('Locale') ?></th>
            <th><?php echo t('Options') ?></th>
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
                <td>
                    <?php

                    echo anchor(t('Edit'), "/subcommunities/$id/edit", 'Popup SmallButton'),
                        anchor(t('Delete'), "/subcommunities/$id/delete", 'Popup SmallButton');
                    ?>
                </td>
            </tr>
        <?php
        endforeach;
        ?>
        </tbody>
    </table>
<?php
PagerModule::write();
