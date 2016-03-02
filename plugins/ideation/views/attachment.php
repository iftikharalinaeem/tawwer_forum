<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Writes attachments for Idea Statuses.
 *
 * @param array $attachment
 */
function WriteStatusAttachment($attachment) {
    ?>
    <div class="item-attachment">
        <div class="alert <?php echo strtolower(val('Statusestate', $attachment)); ?>">
            <div class="media item">
                <div class="pull-left">
                    <div class="media-object">
                        <?php echo Gdn::controller()->data('IdeaCounter', ''); ?>
                    </div>
                </div>
                <div class="media-body">
                    <div class="item-header">
                        <h4 class="media-heading item-heading"><a href="<?php echo val('StatusUrl', $attachment) ?>"><?php echo val('StatusName', $attachment); ?></a> ·
                            <span class="item-meta status-description"><?php echo t('Last Updated').' '.Gdn_Format::Date($attachment['DateUpdated'], 'html'); ?></span>
                            <div class="item-meta status-description"><?php echo val('StatusDescription', $attachment); ?></div>
                        </h4>
                    </div>
                    <div class="item-body">
                        <div class="status-notes"><?php echo val('StatusNotes', $attachment); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
