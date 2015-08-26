<?php if (!defined('APPLICATION')) exit();

class GroupMetaModule extends Gdn_Module {

    public $meta;
    public $group;

    public function __construct($group, $cssClass = '') {
        $this->meta['cssClass'] = $cssClass;
        $this->group = $group;
    }

    public function assetTarget() {
        return 'Content';
    }

    public function getMetaInfo($group) {
        $owner = Gdn::UserModel()->getID(val('InsertUserID', $group));
        $metaItems['owner']['text'] = t('Owner').': ';
        $metaItems['owner']['value'] = userAnchor($owner);

        $groupModel = new GroupModel();
        $leaders = $groupModel->getMembers(val('GroupID', $group), array('Role' => 'Leader'), 10);
        $leaderString = '';
        foreach ($leaders as $leader) {
            $leaderString[] = userAnchor($leader);
        }
        $leaderString = implode(', ', $leaderString);
        $metaItems['leaders']['text'] = t('Leaders').': ';
        $metaItems['leaders']['value'] = $leaderString;

        $date = new DateTime(val('DateInserted', $group));
        $metaItems['date']['text'] = sprintf(t('Created on %s'), $date->format('F j, Y'));

        $metaItems['count']['text'] = sprintf(t('%s members'), val('CountMembers', $group));
        $metaItems['count']['url'] = GroupUrl($group, 'members');

        return $metaItems;
    }

    public function toString() {
        $this->meta['metaItems'] = $this->getMetaInfo($this->group);
        $controller = new Gdn_Controller();
        $controller->setData('meta', $this->meta);
        return $controller->fetchView('groupmeta', 'modules', 'groups');
    }
}

