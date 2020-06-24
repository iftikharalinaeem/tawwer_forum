<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class GroupInfoModule
 */
class GroupInfoModule extends Gdn_Module {

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Content';
    }

    /**
     *
     *
     * @return string
     * @throws Exception
     */
    public function toString() {
        require_once Gdn::controller()->fetchViewLocation('group_functions', 'Group', 'groups');
        ob_start();
        ?>
            <div class="Group-Box Group-Info">
                <h3><?php echo t('Group Info'); ?></h3>
                <?php
                $this->writeGroupInfo();
                ?>
            </div>
        <?php
        $return = ob_get_contents();
        ob_end_clean();
        return $return;
     }

    /**
     *
     */
    public function writeGroupInfo() {
        $c = Gdn::controller();
        $owner = Gdn::userModel()->getID($c->data('Group.InsertUserID'));
        $info = [
            'Created' => Gdn_Format::date($c->data('Group.DateInserted'), 'html'),
            'Owner' => userAnchor($owner),
            'Member Count' => ['Members', $c->data('Group.CountMembers')]
        ];
        echo '<dl class="Group-Info">';
        foreach ($info as $code => $row) {
            if (is_array($row)) {
                $label = t($code, $row[0]);
                $value = $row[1];
            } else {
                $label = t($code);
                $value = $row;
            }
            echo '<dt>'.$label.'</dt>';
            echo '<dd>'.$value.'</dd>';
        }
        echo '</dl>';
     }
}
