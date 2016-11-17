<?php if (!defined('APPLICATION')) exit();

if ($this->BadgeData && $this->BadgeData->numRows() > 0) : ?>

    <ul class="DataList Badges">
        <?php foreach ($this->BadgeData as $Badge) : ?>

        <li class="Item">
            <?php if (checkPermission('Reputation.Badges.Manage')) : ?>
            <div class="Options">
                <div class="ToggleFlyout OptionsMenu">
                    <div class="MenuTitle">Options</div>
                    <ul class="Flyout MenuItems">
                        <li><?php echo anchor(t('Revoke'), '/badge/revoke/'.$this->User->UserID.
                            '/'.val('BadgeID', $Badge).'/'.$Session->transientKey(), 'RevokeBadge'); ?></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <div class="ItemContent Badge">
                <?php echo img(
                    Gdn_Upload::url(changeBasename(val('Photo', $Badge), 'n%s')),
                    ['alt' => val('Name', $Badge), 'class' => 'BadgePhotoDisplay']
                ); ?>
                <?php echo anchor(val('Name', $Badge), 'badge/'.val('Slug', $Badge), 'Title'); ?>
                <div class="Meta">
                    <span class="DateInserted"><?php echo t('Earned') . ' ' . Gdn_Format::date(val('DateCompleted', $Badge)); ?></span>
                    <?php if (($this->User->UserID == Gdn::session()->UserID || CheckPermission('Reputation.Badges.Manage')) && val('Reason', $Badge)) : ?>
                    <span class="Reason"><?php echo t('Reason') . ': ' . Gdn_Format::text(val('Reason', $Badge)); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </li>

        <?php endforeach; ?>
    </ul>

<?php else : ?>

    <div class="Empty"><?php echo t('No badges yet.'); ?></div>

<?php endif; ?>