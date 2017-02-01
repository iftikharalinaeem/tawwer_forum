<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

?>
<script>
    $( document ).ready(function() {
        $( "#filter-reset" ).click(function(e) {
            $("#filter-form")[0].reset();
            window.location.href = '<?php echo Url('/settings/applog'); ?>';
        });
    });
</script>


<h1>Application Log</h1>
<?php
echo $this->Form->Open(array('action' => URL('/settings/applog'), 'Method' => 'GET', 'id' => 'filter-form'));
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
            <?php echo $this->Form->Label('IP', 'ipaddress');  ?>
            <?php echo $this->Form->TextBox('ipaddress', array('class' => 'InputBox Short')); ?>
        </li>
        <li class="float-left">
            <?php echo $this->Form->Label('Priority', 'priority');  ?>
            <?php echo $this->Form->DropDown('priority', $this->Data['PriorityOptions']); ?>
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
            <th class="el-priority">Priority</th>
            <th class="el-event">Event</th>
            <th class="el-ip">IP Address</th>
            <th class="el-site">Site Name</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $i = 0;
        foreach ($this->Data['Events'] as $ID => $event) {
            $i++;
            $class = 'severity-' . Logger::priorityLabel($event['Priority']);
            $class .= $i%2 == 0 ? ' odd' : ' even';
            $class .= ' LogRow';
            ?>
            <tr class="<?php echo $class; ?>" id="Event_<?php echo $ID; ?>">
                <td title="<?php echo Gdn_Format::DateFull($event['Timestamp']); ?>">
                    <?php echo Gdn_Format::ToDateTime($event['Timestamp']); ?></td>
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
                <td><?php echo htmlspecialchars(Logger::priorityLabel($event['Priority'])); ?></td>
                <td><?php echo htmlspecialchars($event['Event']); ?></td>
                <td><?php echo Anchor($event['IP'], '/settings/applog/?ipaddress='.urlencode($event['IP'])); ?></td>
                <td><?php echo $event['SiteName']; ?></td>
            </tr>

            <?php
            if (count($event['Source']) > 1) { ?>
            <tr id="Source_Event_<?php echo $ID; ?>" style="display:none;">
                <td colspan="7  ">
                    <pre><?php echo json_encode($event['Source'], JSON_PRETTY_PRINT); ?>
                    </pre>
                </td>
            </tr>
            <?php
            }
        }
        ?>
    </tbody>
</table>

<?php PagerModule::Write(); ?>

<div class="Info">

<p>This report is also available in JSON or XML</p>
<ul>
    <li><?php echo Anchor('JSON', '/settings/applog.json?'.$this->Data['CurrentFilter']); ?></li>
    <li><?php echo Anchor('XML', '/settings/applog.xml?'.$this->Data['CurrentFilter']); ?></li>
</ul>

</div>