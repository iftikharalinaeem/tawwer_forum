<?php if (!defined('APPLICATION')) exit();

class GroupInfoModule extends Gdn_Module {

    public function __construct($Sender) {
        parent::__construct($Sender, 'Vanilla');
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
            WriteGroupInfo();
            ?>
        </div>

        <?php
        $return = ob_get_contents();
        ob_end_clean();
        return $return;
    }
}
