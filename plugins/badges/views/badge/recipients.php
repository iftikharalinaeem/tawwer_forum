<?php if (!defined('APPLICATION')) exit(); ?>

<div class="header-block">
    <h1><?php echo sprintf(t('%s recipients'), $this->data('Badge.Name')); ?></h1>

    <!-- View badge link icon goes here -->

</div>
<div class="table-wrap">
    <table id="Recipients" class="table-data">
        <thead>
            <tr>
                <th class="BadgeNameHead column-xl"><?php echo t('Badge Recipient Name', 'Recipient'); ?></th>
                <th class="column-md"><?php echo t('Badge Earned Date', 'Earned'); ?></th>
                <th class="options column-md"></th>
            </tr>
        </thead>
        <tbody>
        <?php
        if (count($this->data('Recipients'))) :
            foreach($this->data('Recipients') as $recipient) : ?>
            <tr>
                <td>
                    <div class="media-sm">
                        <div class="media-sm-image-wrap-no-border">
                        <?php echo userPhoto($recipient); ?>
                        </div>
                        <div class="media-sm-content">
                            <div class="media-sm-title strong">
                                <?php echo userAnchor($recipient); ?>
                            </div>
                            <div class="media-sm-description"><?php echo htmlspecialchars(val('Reason', $recipient)); ?></div>
                        </div>
                    </div>
                </td>
                <td><?php echo Gdn_Format::date(val('DateCompleted', $recipient), 'html'); ?></td>
                <td><?php
                    echo anchor(t('Revoke'),
                        '/badge/revoke/'.val('UserID', $recipient).'/'.$this->data('Badge.BadgeID'),
                        'js-modal-confirm js-hijack btn btn-primary revoke-badge',
                        ['aria-label' => t('Revoke'), 'title' => t('Revoke')]
                    ); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else :
            echo '<tr><td colspan="4">' . t('No recipients yet.') . '</td></tr>';
        endif;
        ?>
        </tbody>
    </table>
</div>
