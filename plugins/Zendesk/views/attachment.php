<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Writes attachments for Zendesk Issues.
 *
 * @param array $attachment
 */
function WriteZendeskTicketAttachment($attachment) {

    // Don't display anything for guest.
    if (!Gdn::Session()->IsValid()) {
        return;
    }

    // Only display to Staff
    if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
        return;
    }

    ?>
    <div class="item-attachment">
        <div class="alert">
            <div class="media item">
                <div class="pull-left">
                    <div class="media-object">
                        <i class="icon icon-ticket"></i>
                    </div>
                </div>
                <div class="media-body">

                    <div class="item-header">
                        <h4 class="media-heading item-heading"><?php echo T('Ticket') ?> ·
                            <a href="<?php echo $attachment['SourceURL']; ?>" target="_blank"><?php echo ucfirst($attachment['Source']); ?></a>
                            <div class="item-meta">
                        <span><?php echo Gdn_Format::Date($attachment['DateInserted'], 'html') ?> <?php echo T('by'); ?>
                            <?php echo $attachment['InsertUser']['ProfileLink']; ?></span>
                            </div>
                        </h4></div>

                    <div class="item-body">

                        <dl class="dl-columns">

                            <dt><?php echo T('Issue Number'); ?></dt>
                            <dd><a href="<?php echo $attachment['SourceURL']; ?>" target="_blank"><?php echo $attachment['SourceID']; ?></a></dd>

                            <?php if (GetValue('Status', $attachment)) { ?>
                                <dt><?php echo T('Status'); ?></dt>
                                <dd><?php echo $attachment['Status']; ?></dd>
                            <?php } ?>

                            <?php if (GetValue('Last_Updated', $attachment)) { ?>
                                <dt><?php echo T('Last Updated'); ?></dt>
                                <dd><?php echo Gdn_Format::Date($attachment['LastModifiedDate'], 'html') ?></dd>
                            <?php } ?>
                        </dl>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php
}
