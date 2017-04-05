<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Writes attachments for GitHub Issues.
 *
 * @param array $attachment
 */
function writeGithubIssueAttachment($attachment) {
    ?>
    <div class="item-attachment">
        <div class="alert">
            <div class="media item">
                <div class="pull-left">
                    <div class="media-object">
                        <i class="icon icon-github"></i>
                    </div>
                </div>
                <div class="media-body">

                    <div class="item-header">
                        <h4 class="media-heading item-heading"><?php echo t('Issue') ?> ·
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
                            <dt><?php echo t('State'); ?></dt>
                            <dd><?php echo $attachment['State']; ?></dd>

                            <dt><?php echo t('Last Updated'); ?></dt>
                            <dd><?php echo Gdn_Format::date($attachment['LastModifiedDate'], 'html') ?></dd>
                            <?php if (getValue('Assignee', $attachment)) { ?>
                                <dt><?php echo t('Assignee'); ?></dt>
                                <dd><?php echo Gdn_Format::text($attachment['Assignee']); ?></dd>
                            <?php } ?>
                            <?php if (getValue('Milestone', $attachment)) { ?>
                                <dt><?php echo t('Milestone'); ?></dt>
                                <dd><?php echo Gdn_Format::text($attachment['Milestone']); ?></dd>
                            <?php } ?>
                            <?php if (getValue('ClosedBy', $attachment)) { ?>
                                <dt><?php echo t('Closed By'); ?></dt>
                                <dd><?php echo anchor(
                                        $attachment['ClosedBy'],
                                        'http://github.com/' . $attachment['ClosedBy'],
                                        ['Target' => '_blank']
                                    );
                                    ?>
                                </dd>
                            <?php } ?>

                        </dl>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php
}
