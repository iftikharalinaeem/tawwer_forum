<?php

class GroupInfoModule extends Gdn_Module {

    public function __construct() {
    }

    public function assetTarget() {
        return 'Content';
    }

    public function toString() {
        require_once Gdn::Controller()->FetchViewLocation('group_functions', 'Group', 'groups');
        ob_start();
        ?>

        <div class="Group-Box Group-Info">
            <h3><?php echo T('Group Info'); ?></h3>
            <?php
            $this->WriteGroupInfo();
            ?>
        </div>

        <?php
        $return = ob_get_contents();
        ob_end_clean();
        return $return;
    }

    public function WriteGroupInfo() {
        $c = Gdn::Controller();
        $Owner = Gdn::UserModel()->GetID($c->Data('Group.InsertUserID'));
        $Info = array(
            'Created' => Gdn_Format::Date($c->Data('Group.DateInserted'), 'html'),
            'Owner' => UserAnchor($Owner),
            'Member Count' => array('Members', $c->Data('Group.CountMembers'))
        );
        echo '<dl class="Group-Info">';
        foreach ($Info as $Code => $Row) {
            if (is_array($Row)) {
                $Label = T($Code, $Row[0]);
                $Value = $Row[1];
            } else {
                $Label = T($Code);
                $Value = $Row;
            }

            echo '<dt>'.$Label.'</dt>';
            echo '<dd>'.$Value.'</dd>';
        }
        echo '</dl>';
    }
}
