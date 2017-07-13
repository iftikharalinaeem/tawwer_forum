<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::session();
?>
<h1><?php echo t('Manage Badge Requests'); ?></h1>
<?php
    echo $this->Form->open(['action' => url('/badge/requests')]);
    echo $this->Form->errors();
    $NumRequests = $this->RequestData->numRows();
    if ($NumRequests == 0) { ?>
        <div class="padded italic"><?php echo t('There are currently no requests.'); ?></div>
    <?php } else {
        $AppText = plural($NumRequests, 'There is currently %s request.', 'There are currently %s requests.'); ?>
        <div>
            <?php echo $this->Form->button('Approve', ['type' => 'submit', 'name' => 'Submit', 'value' => 'Approve', 'class' => 'btn btn-primary']); ?>
            <?php echo $this->Form->button('Decline', ['type' => 'submit', 'name' => 'Submit', 'value' => 'Decline', 'class' => 'btn btn-primary']); ?>
        </div>
    <?php } ?>
<div class="padded italic"><?php echo sprintf($AppText, $NumRequests); ?></div>
<div class="table-wrap">
<table class="table-data js-tj">
    <thead>
        <tr>
            <td class="column-checkbox" data-tj-ignore=true></td>
            <th class=""><?php echo t('User'); ?></th>
            <th class="column-lg" data-tj-main=true><?php echo t('BadgeRequestColumnLabel', 'Request'); ?></th>
            <th class="column-md"><?php echo t('Date'); ?></th>
            <th class="options column-sm"></th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($this->RequestData->format('Text')->result() as $Request) { ?>
        <tr>
            <td><?php echo $this->Form->checkBox('Requests[]', '', ['value' => $Request->UserID.'-'.$Request->BadgeID]); ?></td>
            <td><?php echo userAnchor($Request); ?></td>
            <td><?php echo wrap(anchor($Request->BadgeName, 'badge/'.$Request->Slug), 'strong'), '<br />&ldquo;', $Request->RequestReason, '&rdquo;'; ?></td>
            <td><?php echo Gdn_Format::date($Request->DateRequested); ?></td>
            <td class="options">
                <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('checkmark'), '/badge/approve/'.$Request->UserID.'/'.$Request->BadgeID.'/'.$Session->transientKey(), 'btn btn-icon', ['aria-label' => t('Approve')]);
                    echo anchor(dashboardSymbol('delete'), '/badge/decline/'.$Request->UserID.'/'.$Request->BadgeID.'/'.$Session->transientKey(), 'btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete')]);
                    ?>
                </div>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>
<?php
echo $this->Form->close();
