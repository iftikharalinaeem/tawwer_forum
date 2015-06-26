<?php if (!defined('APPLICATION')) exit();

class GroupMetaModule extends Gdn_Module {

  public $info = array();

  public function __construct() {

  }

  public function AssetTarget() {
    return 'Content';
  }

  public function getInfo() {
    $c = Gdn::Controller();
    $owner = Gdn::UserModel()->getID($c->Data('Group.InsertUserID'));
    $groupModel = new GroupModel();
    $leaders = $groupModel->getMembers($c->Data('Group.GroupID'), array('Role' => 'Leader'), 10);
    $leaderString = '';
    foreach ($leaders as $leader) {
      $leaderString[] = userAnchor($leader);
    }
    $date = new DateTime($c->Data('Group.DateInserted'));
    $leaderString = implode(', ', $leaderString);
    $this->info = array(
      'Created' => $date->format('F j, Y'),
      'Owner' => userAnchor($owner),
      'Member Count' => array('Members', $c->Data('Group.CountMembers')),
      'Leaders' => $leaderString
    );
  }

  public function ToString() {
    $this->getInfo();
    $return = '';

    $return .= '<div class="Meta Group-Meta Group-Info">';
    foreach ($this->info as $code => $row) {
      if (is_array($row)) {
        $label = T($code, $row[0]);
        $value = $row[1];
      } else {
        $label = T($code);
        $value = $row;
      }

      $return .= '<span class="MItem">';
      $return .= '<span class="label">'.$label.': </span>';
      $return .= '<span class="value">'.$value.'</span>';
      $return .= '</span>';
    }
    $return .= '</div>';

    return $return;
  }
}
