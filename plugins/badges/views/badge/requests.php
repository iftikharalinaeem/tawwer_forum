<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo T('Manage Badge Requests'); ?></h1>
<?php
    echo $this->Form->Open(array('action' => Url('/badge/requests')));
    echo $this->Form->Errors();
    $NumRequests = $this->RequestData->NumRows();
    if ($NumRequests == 0) { ?>
        <div class="Info"><?php echo T('There are currently no requests.'); ?></div>
    <?php } else { ?>
<?php
    $AppText = Plural($NumRequests, 'There is currently %s request.', 'There are currently %s requests.');
?>
<div class="Info"><?php echo sprintf($AppText, $NumRequests); ?></div>
<table class="CheckColumn">
    <thead>
        <tr>
            <td><?php echo T('Action'); ?></td>
            <th class="Alt"><?php echo T('User'); ?></th>
            <th><?php echo T('BadgeRequestColumnLabel', 'Request'); ?></th>
            <th class="Alt"><?php echo T('Date'); ?></th>
            <th><?php echo T('Options'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($this->RequestData->Format('Text')->Result() as $Request) {
    ?>
        <tr>
            <td><?php echo $this->Form->CheckBox('Requests[]', '', array('value' => $Request->UserID.'-'.$Request->BadgeID)); ?></td>
            <td class="Alt"><?php echo UserAnchor($Request); ?></td>
            <td class="Alt"><?php
                echo Wrap(Anchor($Request->BadgeName, 'badge/'.$Request->Slug), 'strong'), '<br />&ldquo;', $Request->RequestReason, '&rdquo;';
            ?></td>
            <td class="Alt"><?php echo Gdn_Format::Date($Request->DateRequested); ?></td>
            <td><?php
            echo Anchor(T('Approve'), '/badge/approve/'.$Request->UserID.'/'.$Request->BadgeID.'/'.$Session->TransientKey())
                .', '.Anchor(T('Decline'), '/badge/decline/'.$Request->UserID.'/'.$Request->BadgeID.'/'.$Session->TransientKey());
            ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<div class="Info">
<?php
    echo $this->Form->Button('Approve', array('Name' => 'Submit', 'class' => 'SmallButton'));
    echo $this->Form->Button('Decline', array('Name' => 'Submit', 'class' => 'SmallButton'));
?></div><?php
}
echo $this->Form->Close();