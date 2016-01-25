<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Writes attachments for Idea Stage.
 *
 * @param array $attachment
 */

echo('in view');

function WriteStageAttachment($attachment) {
    echo('attachme');

    // Don't display anything for guest.
//    if (!Gdn::Session()->IsValid()) {
//        return;
//    }
//
//    // Only display to Staff
//    if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
//        return;
//    }

    ?>
    <div class="item-attachment">
        <div class="alert">
            <div class="media item">
                <div class="pull-left">
                    <div class="media-object">
                        <i class="icon icon-ticket"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
