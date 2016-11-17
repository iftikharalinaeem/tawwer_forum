<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::session(); ?>
<?php
$Alt = false;
foreach ($this->data('Badges') as $Badge) :
    $Alt = !$Alt;
    $AjaxString = $Session->transientKey().'?Target='.urlencode($this->SelfUrl);

    $badgeBlock = new MediaItemModule(UserBadgeModel::badgeName((array)$Badge), 'badge/'.$Badge->BadgeID, Gdn_Format::text($Badge->Body));
    $badgeBlock->setView('media-sm')
        ->addCssClass('image-wrap', 'media-image-wrap-no-border')
        ->setImageIf($Badge->Photo ? true : false, Gdn_Upload::url($Badge->Photo), '', '', $Badge->Name);
    ?>

    <tr class="<?php
        if ($Alt) {
            echo 'Alt ';
        }
        if (!$Badge->Visible) {
            echo 'HiddenBadge';
        } ?>">
        <td>
            <?php echo $badgeBlock; ?>
        </td>
        <td><?php echo Gdn_Format::text($Badge->Class); ?></td>
        <td><?php echo Gdn_Format::text($Badge->Level); ?></td>
        <td><?php
            if ($Badge->CountRecipients == 0) {
                echo 0;
            } else {
                echo anchor($Badge->CountRecipients, '/badge/recipients/'.$Badge->BadgeID);
            }
        ?></td>
        <!--<td><?php
            // Hide badge
            if (checkPermission('Reputation.Badges.Manage')) {
                echo anchor(t($Badge->Visible == '1' ? 'Yes' : 'No'),
                    '/badge/hide/'.$Badge->BadgeID.'/'.$AjaxString,
                    'HideBadge', ['title'=> ($Badge->Visible ? 'Hide' : 'Show')]);
            } else {
                echo Gdn_Format::text(($Badge->Visible) ? 'Yes' : 'No');
            } ?>
        </td>-->

        <td class="options">
            <div class="btn-group">
            <?php
            if (checkPermission('Reputation.Badges.Manage')) {
                echo anchor(dashboardSymbol('edit'), '/badge/manage/'.$Badge->BadgeID, 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
            }
            if (checkPermission('Reputation.Badges.Manage') && $Badge->CanDelete) {
                echo anchor(dashboardSymbol('delete'), '/badge/delete/'.$Badge->BadgeID.'/?Target='.urlencode($this->SelfUrl), 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete'), 'data-body' => t('Are you sure you want to delete this badge?')]);
            }
            if (checkPermission('Reputation.Badges.Give') && $Badge->Active) {
                echo anchor(dashboardSymbol('give-badge'), '/badge/give/'.$Badge->BadgeID, 'js-modal btn btn-icon', ['title' => t('Give Badge'), 'aria-label' => t('Give Badge')]);
            }
            if (checkPermission('Reputation.Badges.Manage')) {
                echo activateBadge($Badge, $AjaxString);
            }
            ?>
            </div>
        </td>
    </tr>

<?php endforeach;

/**
 *
 *
 * @param $badge
 * @param $ajaxString
 * @return string
 */
function activateBadge($badge, $ajaxString) {
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
