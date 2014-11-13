<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

?>
<script>
    $( document ).ready(function() {
        $( "#filter-reset" ).click(function() {
            $("#filter-form")[0].reset();
            window.location.href = '<?php echo Url('/settings/eventlog2'); ?>';
        });
    });
</script>

<style>
    .cf:before,
    .cf:after {
        content: " "; /* 1 */
        display: table; /* 2 */
    }

    .cf:after {
        clear: both;
    }

    .odd {background-color: #f4f4f4;}
    tr:hover {
        background-color: lightyellow;
    }
    .float-left {
        float: left;
    }
    .floatfix {
        float: left;
        width: 100%
    }
    .blockgrid {
        margin-left: -20px;
    }
    .blockgrid li.float-left {
        padding: 0 20px;
    }
    input.Short {
        width: 165px;
    }
    .buttons {
        margin-top: 15px;
    }
    .table-el {
        table-layout: fixed;
    }

    .table-el th,
    .table-el td {
        padding: 5px 8px;
        word-wrap: break-word;
    }

    .el-date {
        width: 100px;
    }
    .el-user {
        width: 100px;
    }
    .el-severity {
        width: 75px;
    }
    .el-event {
        width: 150px;
    }
    .el-ip {
        width: 100px;
    }

/*<th class="el-date">Date</th>*/
/*<th class="el-message">Message</th>*/
/*<th class="el-user">User</th>*/
/*<th class="el-severity">Severity</th>*/
/*<th class="el-event">Event</th>*/
/*<th class="el-ip">IP Address</th>*/
</style>

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
        foreach ($this->Data['Events'] as $event) {
            $i++;

            ?>
            <tr class="severity-<?php echo Logger::priorityLabel($event['Level']); echo $i%2 == 0 ? ' odd' : ' even' ;?>">
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