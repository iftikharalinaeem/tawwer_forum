<?php if (!defined('APPLICATION')) exit();

class GroupMetaModule extends Gdn_Module {

  public $meta;
  public $group;

  public function __construct($group, $cssClass = 'Group-Meta Group-Info') {
    $this->meta['cssClass'] = $cssClass;
    $this->group = $group;
  }

  public function AssetTarget() {
    return 'Content';
  }

  public function getMetaInfo($group) {
    $owner = Gdn::UserModel()->getID(val('InsertUserID', $group));
    $metaItems['owner']['text'] = T('Owner').': ';
    $metaItems['owner']['value'] = userAnchor($owner);

    $groupModel = new GroupModel();
    $leaders = $groupModel->getMembers(val('GroupID', $group), array('Role' => 'Leader'), 10);
    $leaderString = '';
    foreach ($leaders as $leader) {
      $leaderString[] = userAnchor($leader);
    }
    $leaderString = implode(', ', $leaderString);
    $metaItems['leaders']['text'] = T('Leaders').': ';
    $metaItems['leaders']['value'] = $leaderString;

    $date = new DateTime(val('DateInserted', $group));
    $metaItems['date']['text'] = sprintf(T('Created on %s'), $date->format('F j, Y'));

    $metaItems['count']['text'] = sprintf(T('%s members'), val('CountMembers', $group));
    $metaItems['count']['url'] = GroupUrl($group, 'members');

    return $metaItems;
  }

  public function ToString() {
    $this->meta['metaItems'] = $this->getMetaInfo($this->group);
    $controller = new Gdn_Controller();
    $controller->setData('meta', $this->meta);
    return $controller->fetchView('groupmeta', 'modules', 'groups');
  }
}

