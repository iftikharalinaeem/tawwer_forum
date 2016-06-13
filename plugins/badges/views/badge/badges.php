<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session(); ?>

<?php
$Alt = FALSE;
foreach ($this->Data('Badges') as $Badge) :
    $Alt = !$Alt;
    $AjaxString = $Session->TransientKey().'?Target='.urlencode($this->SelfUrl);  ?>

    <tr class="<?php if ($Alt) echo 'Alt '; if (!$Badge->Visible) echo 'HiddenBadge'; ?>">

        <td>
            <?php if ($Badge->Photo) : ?>
                <?php echo Img(Gdn_Upload::Url($Badge->Photo),
                    array('height' => '25px', 'width' => '25px', 'class' => 'BadgePhoto')); ?>
            <?php endif; ?>
            <strong class="BadgeName"><?php echo Anchor(UserBadgeModel::BadgeName((array)$Badge), 'badge/'.$Badge->BadgeID, 'Title'); ?></strong>
        </td>

        <?php if (CheckPermission('Reputation.Badges.Give')) : ?>
        <td><?php
            if ($Badge->Active) { // $Badge->Type == 'Manual'
                // Give badge
                if ($Session->CheckPermission('Reputation.Badges.Give') && $Badge->Active)
                    echo Anchor(T('Give'), '/badge/give/'.$Badge->BadgeID, 'GiveBadge SmallButton js-give-badge');
            } ?>
        </td>
        <?php endif; ?>

        <td><?php echo Gdn_Format::Text($Badge->Body); ?></td>
        <td><?php echo Gdn_Format::Text($Badge->Class); ?></td>
        <td><?php echo Gdn_Format::Text($Badge->Level); ?></td>
        <td><?php echo Gdn_Format::Text($Badge->CountRecipients); ?></td>

        <td><?php
             // Disable badge
            if (CheckPermission('Reputation.Badges.Manage')) {
                echo Anchor(T($Badge->Active ? 'Yes' : 'No'),
                    '/badge/disable/'.$Badge->BadgeID.'/'.$AjaxString,
                    'DisableBadge', array('title'=> ($Badge->Active ? 'Click to Disable' : 'Click to Enable')));
            }
            else
                echo Gdn_Format::Text(($Badge->Active) ? 'Yes' : 'No'); ?>
        </td>

        <!--<td><?php
            // Hide badge
            if (CheckPermission('Reputation.Badges.Manage')) {
                echo Anchor(T($Badge->Visible == '1' ? 'Yes' : 'No'),
                    '/badge/hide/'.$Badge->BadgeID.'/'.$AjaxString,
                    'HideBadge', array('title'=> ($Badge->Visible ? 'Hide' : 'Show')));
            }
            else
                echo Gdn_Format::Text(($Badge->Visible) ? 'Yes' : 'No'); ?>
        </td>-->

        <td><?php
            // Edit badge
            if (CheckPermission('Reputation.Badges.Manage'))
                echo Anchor(T('Edit'), '/badge/manage/'.$Badge->BadgeID, 'EditBadge SmallButton btn btn-edit');

            // Delete badge
            if (CheckPermission('Reputation.Badges.Manage') && $Badge->CanDelete)
                echo Anchor(T('Delete'), '/badge/delete/'.$Badge->BadgeID.'/'.$AjaxString, 'DeleteBadge Popup SmallButton btn btn-delete'); ?>
        </td>

    </tr>

<?php endforeach; ?>
