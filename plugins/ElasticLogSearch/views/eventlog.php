<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

?>
<script>
    $( document ).ready(function() {
        $( "#filter-reset" ).click(function(e) {
            $("#filter-form")[0].reset();
            window.location.href = '<?php echo Url('/settings/eventlog2'); ?>';
        });
    });
</script>


<h1>Event Logs</h1>
<?php
echo $this->Form->Open(array('action' => URL('/settings/eventlog2'), 'Method' => 'GET', 'id' => 'filter-form'));
echo $this->Form->Errors();
?>
<div class="floatfix">
    <ul class="cf blockgrid">
        <li class="float-left">
            <?php echo $this->Form->Label('Date From', 'datefrom');  ?>
            <?php echo $this->Form->TextBox('datefrom', array('class' => 'InputBox Short')); ?>
        </li>
        <li class="float-left">
            <?php echo $this->Form->Label('Date To', 'dateto');  ?>
            <?php echo $this->Form->TextBox('dateto', array('class' => 'InputBox Short')); ?>
        </li>
        <li class="float-left">
            <?php echo $this->Form->Label('Event Name', 'event');  ?>
            <?php echo $this->Form->TextBox('event', array('class' => 'InputBox Short')); ?>
        </li>
        <li class="float-left">
            <?php echo $this->Form->Label('Severity', 'severity');  ?>
            <?php echo $this->Form->DropDown('severity', $this->Data['SeverityOptions']); ?>
        </li>
        <li class="float-left">
            <?php echo $this->Form->Label('Sort Order', 'sortorder');  ?>
            <?php echo $this->Form->DropDown('sortorder', array('desc' => 'DESC', 'asc' => 'ASC')); ?>

        </li>
        <li class="float-left buttons">
            <?php echo $this->Form->Button("Filter"); ?>
            <?php echo $this->Form->Button("Reset", array('id' => 'filter-reset', 'type' => 'reset')); ?>
            <?php echo $this->Form->Close(); ?>

        </li>
    </ul>
</div>
<?php PagerModule::Write(array('Sender' => $this)); ?>

<table class="AltColumns table-el">
    <thead>
        <tr>
            <th class="el-date">Date</th>
            <th class="el-message">Message</th>
            <th class="el-user">User</th>
            <th class="el-severity">Severity</th>
            <th class="el-event">Event</th>
            <th class="el-ip">IP Address</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $i = 0;
        foreach ($this->Data['Events'] as $ID => $event) {
            $i++;
            $class = 'severity-' . Logger::priorityLabel($event['Level']);
            $class .= $i%2 == 0 ? ' odd' : ' even';
            $class .= ' LogRow';
            ?>
            <tr class="<?php echo $class; ?>" id="Event_<?php echo $ID; ?>">
                <td><?php echo Gdn_Format::DateFull($event['Timestamp'], 'html'); ?></td>
                <td><?php echo htmlspecialchars($event['Message']); ?></td>
                <td class="UsernameCell">
                    <?php
                    $User = Gdn::UserModel()->GetID($event['UserID']);
                    if ($User) {
                        echo UserAnchor($User);
                    } else {
                        echo htmlspecialchars($event['Username']);
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars(Logger::priorityLabel($event['Level'])); ?></td>
                <td><?php echo htmlspecialchars($event['Event']); ?></td>
                <td><?php echo Anchor($event['IP'], Url('/user/browse?Keywords='.urlencode($event['IP']))); ?></td>

            </tr>

            <tr id="Source_Event_<?php echo $ID; ?>" style="display:none;">
                <td colspan="6">
                    <pre><?php echo json_encode($event['Source'], JSON_PRETTY_PRINT); ?>
                    </pre>
                </td>
            </tr>

            <?php
        }
        ?>
    </tbody>
</table>

<?php PagerModule::Write(); ?>

<div class="Info">

<p>This report is also available in JSON or XML</p>
<ul>
    <li><?php echo Anchor('JSON', '/settings/eventlog2.json?'.$this->Data['CurrentFilter']); ?></li>
    <li><?php echo Anchor('XML', '/settings/eventlog2.xml?'.$this->Data['CurrentFilter']); ?></li>
</ul>

</div>