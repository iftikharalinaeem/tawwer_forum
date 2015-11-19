<?php if (!defined('APPLICATION')) exit();

if ($this->BadgeData && $this->BadgeData->NumRows() > 0) : ?>

    <ul class="DataList Badges">
        <?php foreach ($this->BadgeData as $Badge) : ?>

        <li class="Item">
            <?php if (CheckPermission('Reputation.Badges.Manage')) : ?>
            <div class="Options">
                <div class="ToggleFlyout OptionsMenu">
                    <div class="MenuTitle">Options</div>
                    <ul class="Flyout MenuItems">
                        <li><?php echo Anchor(T('Revoke'), '/badge/revoke/'.$this->User->UserID.
                            '/'.GetValue('BadgeID', $Badge).'/'.$Session->TransientKey(), 'RevokeBadge'); ?></li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <div class="ItemContent Badge">
                <?php echo Img(Gdn_Upload::Url(ChangeBasename(GetValue('Photo', $Badge), 'n%s')), array('class' => 'BadgePhotoDisplay')); ?>
                <?php echo Anchor(GetValue('Name', $Badge), 'badge/'.GetValue('Slug', $Badge), 'Title'); ?>
                <div class="Meta">
                    <span class="DateInserted"><?php echo T('Earned') . ' ' . Gdn_Format::Date(GetValue('DateCompleted', $Badge)); ?></span>

                    <?php if (($this->User->UserID == Gdn::Session()->UserID || CheckPermission('Reputation.Badges.Manage')) && GetValue('Reason', $Badge)) : ?>
                    <span class="Reason"><?php echo T('Reason') . ': ' . Gdn_Format::Text(GetValue('Reason', $Badge)); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </li>

        <?php endforeach; ?>
    </ul>

<?php else : ?>

    <div class="Empty"><?php echo T('No badges yet.'); ?></div>

<?php endif; ?>