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
function WriteGithubIssueAttachment($attachment) {
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
                        <h4 class="media-heading item-heading"><?php echo T('Issue') ?> ·
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
                            <dt><?php echo T('State'); ?></dt>
                            <dd><?php echo $attachment['State']; ?></dd>

                            <dt><?php echo T('Last Updated'); ?></dt>
                            <dd><?php echo Gdn_Format::Date($attachment['LastModifiedDate'], 'html') ?></dd>
                            <?php if (GetValue('Assignee', $attachment)) { ?>
                                <dt><?php echo T('Assignee'); ?></dt>
                                <dd><?php echo Gdn_Format::Text($attachment['Assignee']); ?></dd>
                            <?php } ?>
                            <?php if (GetValue('Milestone', $attachment)) { ?>
                                <dt><?php echo T('Milestone'); ?></dt>
                                <dd><?php echo Gdn_Format::Text($attachment['Milestone']); ?></dd>
                            <?php } ?>
                            <?php if (GetValue('ClosedBy', $attachment)) { ?>
                                <dt><?php echo T('Closed By'); ?></dt>
                                <dd><?php echo Anchor(
                                        $attachment['ClosedBy'],
                                        'http://github.com/' . $attachment['ClosedBy'],
                                        array('Target' => '_blank')
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
