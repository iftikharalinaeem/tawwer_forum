<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Writes attachments for Idea Stages.
 *
 * @param array $attachment
 */
function WriteStageAttachment($attachment) {
    ?>
    <div class="item-attachment">
        <div class="alert <?php echo strtolower(val('StageStatus', $attachment)) ?>">
            <div class="media item">
                <div class="pull-left">
                    <div class="media-object">
                            <?php
                            if (Gdn::controller()->data('IdeaCounterModule')) {
                                echo Gdn::controller()->data('IdeaCounterModule')->toString();
                            }
                            ?>
                    </div>
                </div>
                <div class="media-body">
                    <div class="item-header">
                        <h4 class="media-heading item-heading"><a href="<?php echo val('StageUrl', $attachment) ?>"><?php echo val('StageName', $attachment) ?></a> ·
                            <span class="item-meta stage-description"><?php echo T('Last Updated').' '.Gdn_Format::Date($attachment['DateUpdated'], 'html') ?></span>
                            <div class="item-meta stage-description"><?php echo val('StageDescription', $attachment) ?></div>
                        </h4>
                    </div>
                    <div class="item-body">
                        <div class="stage-notes"><?php echo val('StageNotes', $attachment) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
