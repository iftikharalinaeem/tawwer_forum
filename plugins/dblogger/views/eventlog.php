<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
?>
<h1>Event Logs</h1>
<script>
    $( document ).ready(function() {
        $( "#filter-reset" ).click(function() {
            $("#filter-form")[0].reset();
            window.location.href = '<?php echo Url('/settings/eventlog'); ?>';
        });
    });
</script>
<?php
echo $this->Form->Open(array('action' => URL('/settings/eventlog'), 'Method' => 'GET', 'id' => 'filter-form'));
echo $this->Form->Errors();
//$this->Form = new Gdn_Form();
?>
<div class="toolbar">
    <div class="date-from">
        <?php echo $this->Form->TextBox('datefrom', array('class' => 'form-control', 'placeholder' => t('Date From'), 'aria-label' => t('Date From'))); ?>
    </div>
    <div class="date-to">
        <?php echo $this->Form->TextBox('dateto', array('class' => 'form-control', 'placeholder' => t('Date To'), 'aria-label' => t('Date To'))); ?>
    </div>
    <div class="event-name">
        <?php echo $this->Form->TextBox('event', array('class' => 'form-control', 'placeholder' => t('Event Name'), 'aria-label' => t('Event Name'))); ?>
    </div>
    <div class="flex severity">
        <?php echo $this->Form->labelWrap('Severity', 'severity');  ?>
        <div class="input-wrap">
            <?php echo $this->Form->DropDown('severity', $this->Data['SeverityOptions'], ['class' => 'form-control']); ?>
        </div>
    </div>
    <div class="flex sort-order">
        <?php echo $this->Form->labelWrap('Sort Order', 'sortorder');  ?>
        <div class="input-wrap">
            <?php echo $this->Form->DropDown('sortorder', array('desc' => 'DESC', 'asc' => 'ASC'), ['class' => 'form-control']); ?>
        </div>
    </div>
    <div class="buttons">
        <?php echo $this->Form->Button("Filter"); ?>
        <?php echo $this->Form->Button("Reset", array('id' => 'filter-reset', 'type' => 'reset')); ?>
    </div>
</div>
<?php echo $this->Form->Close(); ?>
<?php PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard', 'CssClass' => 'pull-right padded-bottom']); ?>
<div class="table-wrap">
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
</div>

<div class="padded alert alert-info">

<p>This report is also available in JSON or XML</p>
<ul>
    <li><?php echo Anchor('JSON', '/settings/eventlog.json?'.$this->Data['CurrentFilter']); ?></li>
    <li><?php echo Anchor('XML', '/settings/eventlog.xml?'.$this->Data['CurrentFilter']); ?></li>
</ul>

</div>
