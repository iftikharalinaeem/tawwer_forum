<?php
PagerModule::Write(array('Sender' => $this));
?>
    <table id="minisites" class="AltColumns">
        <thead>
        <tr>
            <th><?php echo T('Name'); ?></th>
            <th><?php echo T('Folder') ?></th>
            <th><?php echo T('Locale') ?></th>
            <th><?php echo T('Options') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($this->Data('Sites') as $Row):
            $id = $Row['MinisiteID'];
            ?>
            <tr id="<?php echo "minisite_$id"; ?>">
                <td><?php echo htmlspecialchars($Row['Name']); ?></td>
                <td>
                    <?php
                    $url = Gdn::Request()->UrlDomain('//').'/'.htmlspecialchars($Row['Folder']);
                    echo Anchor('/'.htmlspecialchars($Row['Folder']), $url);
                    ?>
                </td>
                <td><?php echo strtolower($Row['Locale']); ?></td>
                <td>
                    <?php

                    echo Anchor(T('Edit'), "/minisites/$id/edit", 'Popup SmallButton'),
                        Anchor(T('Delete'), "/minisites/$id/delete", 'Popup SmallButton');
                    ?>
                </td>
            </tr>
        <?php
        endforeach;
        ?>
        </tbody>
    </table>
<?php
PagerModule::Write();
