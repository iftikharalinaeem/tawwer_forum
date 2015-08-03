<?php if (!defined('APPLICATION')) exit();

class GroupLeadersModule extends Gdn_Module {

    public function __construct($Sender) {
        parent::__construct($Sender, 'Vanilla');
    }

    public function assetTarget() {
        return 'Content';
    }

    public function toString() {
        require_once Gdn::Controller()->FetchViewLocation('group_functions', 'Group', 'groups');
        $GroupID = Gdn::Controller()->Data('Group.GroupID');
        $GroupModel = new GroupModel();
        $Group = $GroupModel->GetID($GroupID);
        $Leaders = $GroupModel->GetMembers($GroupID, array('Role' => 'Leader'), 10);

        ob_start();
        ?>

        <div class="Group-Box Group-Leaders">
            <h3><?php echo Anchor(T('Group Leaders', 'Leaders'), GroupUrl($Group)); ?></h3>
            <?php WriteMemberSimpleList($Leaders); ?>
        </div>

        <?php
        $return = ob_get_contents();
        ob_end_clean();
        return $return;
    }
}
