<?php
/**
 * Groups Application - Group Header Module
 */

/**
 * Class GroupHeaderModule
 *
 *
 */
class GroupMetaModule extends Gdn_Module {

    /**
     * @var array The compiled meta for the group.
     */
    private $meta;
    /**
     * @var array The group the meta is associated with.
     */
    private $group;

    /**
     * Construct the GroupMetaModule object.
     *
     * @param array $group The group the meta is associated with.
     * @param string $cssClass The css class for the meta container.
     */
    public function __construct($group, $cssClass = '') {
        $this->meta['cssClass'] = $cssClass;
        $this->group = $group;
    }

    /**
     * Collect and organize the data for the group's meta.
     *
     * @param array $group The group the meta is associated with.
     * @return array A meta items data array.
     */
    private function getMetaInfo($group) {
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

    /**
     * Render the group meta.
     *
     * @return string HTML view
     */
    public function toString() {
        $this->meta['metaItems'] = $this->getMetaInfo($this->group);
        $controller = new Gdn_Controller();
        $controller->setData('meta', $this->meta);
        return $controller->fetchView('groupmeta', 'modules', 'groups');
    }
}

