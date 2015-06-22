<?php if (!defined('APPLICATION')) exit();

class GroupMembersModule extends Gdn_Module {

  public function __construct($Sender) {
    parent::__construct($Sender, 'Vanilla');
  }

  public function AssetTarget() {
    return 'Content';
  }

  public function ToString() {
    require_once Gdn::Controller()->FetchViewLocation('group_functions', 'Group', 'groups');
    $GroupID = Gdn::Controller()->Data('Group.GroupID');
    $GroupModel = new GroupModel();
    $Group = $GroupModel->GetID($GroupID);
    $Members = $GroupModel->GetMembers($GroupID, array('Role' => 'Member'), 30);

    ob_start();
    ?>

    <div class="Group-Box Group-MembersPreview">
      <h3><?php echo Anchor(T('Group Members', 'Members'), GroupUrl($Group, 'members'));?></h3>
      <?php WriteMemberGrid($Members, Anchor(sprintf(T('All %s'), T('Members')), GroupUrl($Group, 'members'), 'MoreWrap')); ?>
    </div>

    <?php
    $return = ob_get_contents();
    ob_end_clean();
    return $return;
  }
}
