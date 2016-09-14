<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session(); ?>

<?php
$Alt = FALSE;
foreach ($this->Data('Badges') as $Badge) :
    $Alt = !$Alt;
    $AjaxString = $Session->TransientKey().'?Target='.urlencode($this->SelfUrl);  ?>

    <tr class="<?php if ($Alt) echo 'Alt '; if (!$Badge->Visible) echo 'HiddenBadge'; ?>">

        <td>
            <div class="media-sm">
                <div class="media-sm-image-wrap-no-border">
                <?php if ($Badge->Photo) :
                    echo Img(Gdn_Upload::Url($Badge->Photo), array('class' => 'BadgePhoto'));
                endif; ?>
                </div>
                <div class="media-sm-content">
                    <div class="media-sm-title strong">
                        <?php echo Anchor(UserBadgeModel::BadgeName((array)$Badge), 'badge/'.$Badge->BadgeID, 'Title'); ?>
                    </div>
                    <div class="media-sm-description"><?php echo Gdn_Format::Text($Badge->Body); ?></div>
                </div>
            </div>
        </td>
        <td><?php echo Gdn_Format::Text($Badge->Class); ?></td>
        <td><?php echo Gdn_Format::Text($Badge->Level); ?></td>
        <td><?php echo Gdn_Format::Text($Badge->CountRecipients); ?></td>
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

        <td class="options">
            <div class="btn-group">
            <?php
            if (CheckPermission('Reputation.Badges.Manage')) {
                echo anchor(dashboardSymbol('edit'), '/badge/manage/'.$Badge->BadgeID, 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
            }
            if (CheckPermission('Reputation.Badges.Manage') && $Badge->CanDelete) {
                echo anchor(dashboardSymbol('delete'), '/badge/delete/'.$Badge->BadgeID.'/?Target='.urlencode($this->SelfUrl), 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete'), 'data-content' => ['body' => t('Are you sure you want to delete this badge?')]]);
            }
            if ($Session->CheckPermission('Reputation.Badges.Give') && $Badge->Active) {
                echo anchor(dashboardSymbol('give-badge'), '/badge/give/'.$Badge->BadgeID, 'js-modal btn btn-icon', ['title' => t('Give Badge'), 'aria-label' => t('Give Badge')]);
            }
            if (CheckPermission('Reputation.Badges.Manage')) {
                echo ActivateBadge($Badge, $AjaxString);
            }
            ?>
            </div>
        </td>
    </tr>

<?php endforeach;

function ActivateBadge($badge, $ajaxString) {
    $State = ($badge->Active ? 'Active' : 'InActive');

    $return = '<span id="badges-toggle">';
    if ($State === 'Active') {
        $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/badge/disable/'.$badge->BadgeID.'/'.$ajaxString), 'span', array('class' => "toggle-wrap toggle-wrap-on"));
    } else {
        $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/badge/disable/'.$badge->BadgeID.'/'.$ajaxString), 'span', array('class' => "toggle-wrap toggle-wrap-off"));
    }

    $return .= '</span>';

    return $return;
}

