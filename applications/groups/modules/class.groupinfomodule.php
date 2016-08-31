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
        $Owner = Gdn::userModel()->getID($c->data('Group.InsertUserID'));
        $Info = array(
            'Created' => Gdn_Format::date($c->data('Group.DateInserted'), 'html'),
            'Owner' => UserAnchor($Owner),
            'Member Count' => array('Members', $c->data('Group.CountMembers'))
        );
        echo '<dl class="Group-Info">';
        foreach ($Info as $Code => $Row) {
            if (is_array($Row)) {
                $Label = t($Code, $Row[0]);
                $Value = $Row[1];
            } else {
                $Label = t($Code);
                $Value = $Row;
            }
            echo '<dt>'.$Label.'</dt>';
            echo '<dd>'.$Value.'</dd>';
        }
        echo '</dl>';
     }
}
