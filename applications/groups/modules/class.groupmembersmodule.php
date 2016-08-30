<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Class GroupMembersModule
 */
class GroupMembersModule extends Gdn_Module {

     /**
      * GroupMembersModule constructor.
      *
      * @param object|string $Sender
      */
     public function __construct($Sender) {
          parent::__construct($Sender, 'Vanilla');
     }

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
          require_once Gdn::Controller()->FetchViewLocation('group_functions', 'Group', 'groups');
          $GroupID = Gdn::Controller()->Data('Group.GroupID');
          $GroupModel = new GroupModel();
          $Group = $GroupModel->GetID($GroupID);
          $Members = $GroupModel->GetMembers($GroupID, array('Role' => 'Member'), 30);
          $Leaders = $GroupModel->GetMembers($GroupID, array('Role' => 'Leader'), 30);
          $Members = array_merge($Leaders, $Members);

          ob_start();
          ?>
          <div class="Group-Box Group-MembersPreview">
                <div class="PageControls">
                     <h2 class="Groups H"><?php echo T('Group Members', 'Members');?></h2>
                </div>
                <?php WriteMemberGrid($Members, Anchor(sprintf(T('All %s...'), T('Members')), GroupUrl($Group, 'members'), 'MoreWrap')); ?>
          </div>
          <?php
          $return = ob_get_contents();
          ob_end_clean();

          return $return;
     }
}
