<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * @param $group
 * @return string
 */
function groupSlug($group) {
    return $group['GroupID'].'-'.Gdn_Format::url($group['Name']);
}

function groupUrl($group, $method = null, $withDomain = '//') {
    if ($method) {
        return url("/group/$method/".groupSlug($group), $withDomain);
    } else {
        return url('/group/'.groupSlug($group), $withDomain);
    }
}
function eventSlug($event) {
    return $event['EventID'].'-'.Gdn_Format::url($event['Name']);
}

/**
 * Make an event URL.
 *
 * @param array $event
 * @param null|string $method
 * @return string
 * @deprecated EventModel::eventUrl()
 */
function eventUrl($event, $method = null) {
    if ($method) {
        return url("/event/$method/".eventSlug($event), '//');
    } else {
        return url('/event/'.eventSlug($event), '//');
    }
}

/**
 * Check a group permission.
 *
 * @param null $permission
 * @param null $groupID
 * @return bool|string
 *
 * @deprecated GroupModel::checkGroupPermission()
 */
function groupPermission($permission = null, $groupID = null) {
    deprecated(__FUNCTION__, 'GroupModel::checkGroupPermission');
    if ($groupID === null) {
        $groupID = Gdn::controller()->data('Group');
    }

    /** @var GroupModel $model */
    $model = \Gdn::getContainer()->get(GroupModel::class);
    return $model->checkPermission($permission, $groupID);
}

/**
 * Check an event permission.
 *
 * @param null $permission
 * @param null $eventID
 * @return bool
 * @deprecated
 */
function eventPermission($permission = null, $eventID = null) {
    deprecated(__FUNCTION__, 'EventModel::eventPermission()');
    if ($eventID === null) {
        $eventID = Gdn::controller()->data('Event');
    }

    /** @var EventModel $model */
    $model = \Gdn::getContainer()->get(GroupModel::class);
    return $model->checkPermission($permission, $eventID);
}
