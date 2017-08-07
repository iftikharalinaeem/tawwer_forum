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

function eventUrl($event, $method = null) {
    if ($method) {
        return url("/event/$method/".eventSlug($event), '//');
    } else {
        return url('/event/'.eventSlug($event), '//');
    }
}

function groupPermission($permission = null, $groupID = null) {
    if ($groupID === null) {
        $groupID = Gdn::controller()->data('Group');
    }

    if (isset(Gdn::controller()->GroupModel))
        return Gdn::controller()->GroupModel->checkPermission($permission, $groupID);
    $groupModel = new GroupModel();
    return $groupModel->checkPermission($permission, $groupID);
}

function eventPermission($permission = null, $eventID = null) {
    if ($eventID === null) {
        $eventID = Gdn::controller()->data('Event');
    }

    if (isset(Gdn::controller()->EventModel))
        return Gdn::controller()->EventModel->checkPermission($permission, $eventID);
    $eventModel = new EventModel();
    return $eventModel->checkPermission($permission, $eventID);
}