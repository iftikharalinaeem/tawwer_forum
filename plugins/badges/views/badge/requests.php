<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo t('Manage Badge Requests'); ?></h1>
<?php
    echo $this->Form->Open(array('action' => Url('/badge/requests')));
    echo $this->Form->Errors();
    $NumRequests = $this->RequestData->NumRows();
    if ($NumRequests == 0) { ?>
        <div class="padded italic"><?php echo T('There are currently no requests.'); ?></div>
    <?php } else {
        $AppText = Plural($NumRequests, 'There is currently %s request.', 'There are currently %s requests.'); ?>
        <div>
            <?php echo $this->Form->button('Approve', array('type' => 'submit', 'name' => 'Submit', 'value' => 'Approve', 'class' => 'btn btn-primary')); ?>
            <?php echo $this->Form->button('Decline', array('type' => 'submit', 'name' => 'Submit', 'value' => 'Decline', 'class' => 'btn btn-primary')); ?>
        </div>
    <?php } ?>
<div class="padded italic"><?php echo sprintf($AppText, $NumRequests); ?></div>
<div class="table-wrap">
<table class="table-data">
    <thead>
        <tr>
            <td class="column-checkbox" data-tj-ignore=true></td>
            <th class=""><?php echo T('User'); ?></th>
            <th class="column-lg" data-tj-main=true><?php echo T('BadgeRequestColumnLabel', 'Request'); ?></th>
            <th class="column-md"><?php echo T('Date'); ?></th>
            <th class="options column-sm"></th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($this->RequestData->Format('Text')->Result() as $Request) {
    ?>
        <tr>
            <td><?php echo $this->Form->CheckBox('Requests[]', '', array('value' => $Request->UserID.'-'.$Request->BadgeID)); ?></td>
            <td><?php echo UserAnchor($Request); ?></td>
            <td><?php
                echo Wrap(Anchor($Request->BadgeName, 'badge/'.$Request->Slug), 'strong'), '<br />&ldquo;', $Request->RequestReason, '&rdquo;';
            ?></td>
            <td><?php echo Gdn_Format::Date($Request->DateRequested); ?></td>
            <td class="options">
                <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('checkmark'), '/badge/approve/'.$Request->UserID.'/'.$Request->BadgeID.'/'.$Session->TransientKey(), 'btn btn-icon', ['aria-label' => t('Approve')]);
                    echo anchor(dashboardSymbol('delete'), '/badge/decline/'.$Request->UserID.'/'.$Request->BadgeID.'/'.$Session->TransientKey(), 'btn btn-icon', ['aria-label' => t('Delete')]);
                    ?>
                </div>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>
<?php
echo $this->Form->Close();
