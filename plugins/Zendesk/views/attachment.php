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
function writeZendeskTicketAttachment($attachment) {

    // Don't display anything for guest.
    if (!Gdn::session()->isValid()) {
        return;
    }

    // Only display to Staff
    if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
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
                        <h4 class="media-heading item-heading"><?php echo t('Ticket') ?> ·
                            <a href="<?php echo $attachment['SourceURL']; ?>" target="_blank"><?php echo ucfirst($attachment['Source']); ?></a>
                            <div class="item-meta">
                        <span><?php echo Gdn_Format::date($attachment['DateInserted'], 'html') ?> <?php echo t('by'); ?>
                            <?php echo $attachment['InsertUser']['ProfileLink']; ?></span>
                            </div>
                        </h4></div>

                    <div class="item-body">

                        <dl class="dl-columns">

                            <dt><?php echo t('Issue Number'); ?></dt>
                            <dd><a href="<?php echo $attachment['SourceURL']; ?>" target="_blank"><?php echo $attachment['SourceID']; ?></a></dd>

                            <?php if (getValue('Status', $attachment)) { ?>
                                <dt><?php echo t('Status'); ?></dt>
                                <dd><?php echo $attachment['Status']; ?></dd>
                            <?php } ?>

                            <?php if (getValue('Last_Updated', $attachment)) { ?>
                                <dt><?php echo t('Last Updated'); ?></dt>
                                <dd><?php echo Gdn_Format::date($attachment['LastModifiedDate'], 'html') ?></dd>
                            <?php } ?>
                        </dl>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php
}
