<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
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
        <div class="alert alert-info <?php echo strtolower(val('StatusState', $attachment)); ?>">
            <div class="media">
                <div class="pull-left">
                    <div class="media-object">
                        <?php echo Gdn::controller()->data('IdeaCounter', ''); ?>
                    </div>
                </div>
                <div class="media-body">
                    <h4 class="media-heading status-heading"><a href="<?php echo val('StatusUrl', $attachment) ?>"><?php echo val('StatusName', $attachment); ?></a> ·
                        <small class="status-meta"><?php echo t('Last Updated').' '.Gdn_Format::Date($attachment['DateUpdated'], 'html'); ?></small>
                    </h4>
                    <p class="status-notes"><?php echo val('StatusNotes', $attachment); ?></p>
                </div>
            </div>
        </div>
    </div>
<?php
}
