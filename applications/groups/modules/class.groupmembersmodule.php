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
          require_once Gdn::controller()->fetchViewLocation('group_functions', 'Group', 'groups');
          $GroupID = Gdn::controller()->data('Group.GroupID');
          $GroupModel = new GroupModel();
          $Group = $GroupModel->getID($GroupID);
          $Members = $GroupModel->getMembers($GroupID, ['Role' => 'Member'], 30);
          $Leaders = $GroupModel->getMembers($GroupID, ['Role' => 'Leader'], 30);
          $Members = array_merge($Leaders, $Members);

          ob_start();
          ?>
          <div class="Group-Box Group-MembersPreview">
                <div class="PageControls">
                     <h2 class="Groups H"><?php echo t('Group Members', 'Members');?></h2>
                </div>
                <?php writeMemberGrid($Members, anchor(sprintf(t('All %s...'), t('Members')), groupUrl($Group, 'members'), 'MoreWrap')); ?>
          </div>
          <?php
          $return = ob_get_contents();
          ob_end_clean();

          return $return;
     }
}
