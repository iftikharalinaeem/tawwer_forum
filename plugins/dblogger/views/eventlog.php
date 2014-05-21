<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

?>
<h2>Event Logs</h2>
<?php
echo $this->Form->Open(array('Method' => 'GET'));
echo $this->Form->Errors();
?>
<ul>
    <li>
        <?php echo $this->Form->Label('Date From', 'datefrom');  ?>
        <?php echo $this->Form->TextBox('datefrom'); ?>
    </li>
    <li>
        <?php echo $this->Form->Label('Date To', 'dateto');  ?>
        <?php echo $this->Form->TextBox('dateto'); ?>
    </li>
    <li>
        <?php echo $this->Form->Label('Date From', 'datefrom');  ?>
        <?php echo $this->Form->TextBox('datefrom'); ?>
    </li>
    <li>
        <?php echo $this->Form->Label('Event Name', 'event');  ?>
        <?php echo $this->Form->TextBox('event'); ?>
    </li>
    <li>
        <?php echo $this->Form->Label('Severity', 'severity');  ?>
        <?php echo $this->Form->DropDown('severity', $this->Data['SeverityOptions']); ?>
    </li>
    <li>
        <?php echo $this->Form->Label('Sort Order', 'sortorder');  ?>
        <?php echo $this->Form->DropDown('sortorder', array('desc' => 'DESC', 'asc' => 'ASC')); ?>

    </li>
</ul>

<?php echo $this->Form->Close('Filter', '', array('class' => 'Button BigButton')); ?>

<table class="AltColumns">

    <thead>
        <tr>
            <th class="DateCell">Date</th>
            <th>Event</th>
            <th>LogLevel</th>
            <th>InsertName</th>
            <th>FullPath</th>
            <th>Attributes</th>
            <th>InsertIPAddress</th>
        </tr>
    </thead>

    <tbody>
        <?php
        foreach ($this->Data['Events'] as $event) {
            ?>
            <tr>
                <td class="DateCell"><?php echo $event->DateTimeInserted; ?></td>
                <td><?php echo $event->Event; ?></td>
                <td><?php echo $event->LogLevel; ?></td>
                <td class="UsernameCell">
                    <?php echo Anchor($event->InsertName, $event->InsertProfileUrl); ?>
                </td>
                <td><?php echo $event->FullPath; ?></td>
                <td >
                    <?php echo $event->Attributes; ?>
                </td>
                <td><?php echo $event->InsertIPAddress; ?></td>

            </tr>
            <?php
        }
        ?>
    </tbody>
</table>

<div class="Info">

<p>Event logs are available in JSON or XML</p>
<ul>
    <li><?php echo "<a href='". Url('/settings/evenetlog.json') ."'>JSON</a>"; ?></li>
    <li><?php echo "<a href='". Url('/settings/evenetlog.xml') ."'>XML</a>"; ?></li>
</ul>

</div>