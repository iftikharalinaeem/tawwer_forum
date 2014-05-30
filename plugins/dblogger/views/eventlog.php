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
            window.location.href = '<?php echo Url('/settings/eventlog'); ?>';
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
        margin-right: -20px;
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
</style>

<h1>Event Logs</h1>
<?php
echo $this->Form->Open(array('action' => URL('/settings/eventlog'), 'Method' => 'GET', 'id' => 'filter-form'));
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

<table class="AltColumns">

    <thead>
        <tr>
            <th>Date</th>
            <th>Message</th>
            <th>User</th>
            <th>Severity</th>
            <th>Event</th>
            <th>IP Address</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $i = 0;
        foreach ($this->Data['Events'] as $event) {
            $i++;

            ?>
            <tr class="severity-<?php echo strtolower($event['LogLevel']); echo $i%2 == 0 ? ' odd' : ' even' ;?>">
                <td><?php echo Gdn_Format::DateFull($event['DateTimeInserted'], 'html'); ?></td>
                <td><?php echo htmlspecialchars($event['Message']); ?></td>
                <td class="UsernameCell">
                    <?php echo Anchor($event['InsertName'], $event['InsertProfileUrl']); ?>
                </td>
                <td><?php echo htmlspecialchars($event['LogLevel']); ?></td>
                <td><?php echo htmlspecialchars($event['Event']); ?></td>
                <td><?php echo Anchor($event['InsertIPAddress'], Url('/user/browse?Keywords=') . $event['InsertIPAddress']); ?></td>

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
    <li><?php echo Anchor('JSON', '/settings/eventlog.json?'.$this->Data['CurrentFilter']); ?></li>
    <li><?php echo Anchor('XML', '/settings/eventlog.xml?'.$this->Data['CurrentFilter']); ?></li>
</ul>

</div>