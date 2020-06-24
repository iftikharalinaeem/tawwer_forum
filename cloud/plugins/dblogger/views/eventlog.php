<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
?>
<h1>Event Logs</h1>
<script>
    $( document ).ready(function() {
        $( "#filter-reset" ).click(function() {
            $("#filter-form")[0].reset();
            window.location.href = '<?php echo url('/settings/eventlog'); ?>';
        });
    });
</script>
<?php
echo $this->Form->open(['action' => uRL('/settings/eventlog'), 'Method' => 'GET', 'id' => 'filter-form']);
echo $this->Form->errors();
//$this->Form = new gdn_Form();
?>
<div class="toolbar flex-wrap">
    <div class="date-from">
        <?php echo $this->Form->textBox('datefrom', ['class' => 'form-control', 'placeholder' => t('Date From'), 'aria-label' => t('Date From')]); ?>
    </div>
    <div class="date-to">
        <?php echo $this->Form->textBox('dateto', ['class' => 'form-control', 'placeholder' => t('Date To'), 'aria-label' => t('Date To')]); ?>
    </div>
    <div class="event-name">
        <?php echo $this->Form->textBox('event', ['class' => 'form-control', 'placeholder' => t('Event Name'), 'aria-label' => t('Event Name')]); ?>
    </div>
    <div class="flex severity">
        <?php echo $this->Form->labelWrap('Severity', 'severity');  ?>
        <div class="input-wrap">
            <?php echo $this->Form->dropDown('severity', $this->Data['SeverityOptions'], ['class' => 'form-control']); ?>
        </div>
    </div>
    <div class="flex sort-order">
        <?php echo $this->Form->labelWrap('Sort Order', 'sortorder');  ?>
        <div class="input-wrap">
            <?php echo $this->Form->dropDown('sortorder', ['desc' => 'DESC', 'asc' => 'ASC'], ['class' => 'form-control']); ?>
        </div>
    </div>
    <div class="flex">
        <?php echo $this->Form->button("Filter"); ?>
        <?php echo $this->Form->button("Reset", ['id' => 'filter-reset', 'type' => 'reset']); ?>
    </div>
    <?php PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard']); ?>
</div>
<?php echo $this->Form->close(); ?>
<div class="table-wrap">
<table class="AltColumns table-el table-data js-tj">
    <thead>
        <tr>
            <th class="el-date column-lg">Date</th>
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
                <td><?php echo Gdn_Format::dateFull($event['Timestamp'], 'html'); ?></td>
                <td><?php echo htmlspecialchars($event['Message']); ?></td>
                <td class="UsernameCell">
                    <?php
                    $User = Gdn::userModel()->getID($event['UserID']);
                    if ($User) {
                        echo userAnchor($User);
                    } else {
                        echo htmlspecialchars($event['Username']);
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars(Logger::priorityLabel($event['Level'])); ?></td>
                <td><?php echo htmlspecialchars($event['Event']); ?></td>
                <td><?php
                    $iP = ipDecode($event['IP']);
                    if (filter_var($iP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        echo anchor($iP, url('/user/browse?Keywords='.urlencode($iP)), ['title' => $iP]);
                    } elseif (filter_var($iP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        echo anchor('IPV6', url('/user/browse?Keywords='.urlencode($iP)), ['title' => $iP]);
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
</div>

<div class="padded alert alert-info">

<p>This report is also available in JSON or XML</p>
<ul>
    <li><?php echo anchor('JSON', '/settings/eventlog.json?'.$this->Data['CurrentFilter']); ?></li>
    <li><?php echo anchor('XML', '/settings/eventlog.xml?'.$this->Data['CurrentFilter']); ?></li>
</ul>

</div>
