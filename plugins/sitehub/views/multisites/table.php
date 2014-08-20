<?php
PagerModule::Write(array('Sender' => $this));
?>
<table id="multisites" class="AltColumns">
    <thead>
    <tr>
        <th><?php echo T('Name'); ?></th>
        <th><?php echo T('Url') ?></th>
        <th><?php echo T('Last Sync') ?></th>
        <th><?php echo T('Status') ?></th>
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
                echo Gdn_Format::Date($Row['DateLastSync'], 'html');
                ?>
            </td>
            <td><?php echo strtolower($Row['Status']); ?></td>
        </tr>
    <?php
    endforeach;
    ?>
    </tbody>
</table>
<?php
PagerModule::Write();