<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * @param $group
 * @return string
 */
function GroupSlug($group) {
    return $group['GroupID'].'-'.Gdn_Format::Url($group['Name']);
}

function GroupUrl($group, $method = null, $withDomain = '//') {
    if ($method) {
        return Url("/group/$method/".GroupSlug($group), $withDomain);
    } else {
        return Url('/group/'.GroupSlug($group), $withDomain);
    }
}
function EventSlug($event) {
    return $event['EventID'].'-'.Gdn_Format::Url($event['Name']);
}

function EventUrl($event, $method = null) {
    if ($method) {
        return Url("/event/$method/".EventSlug($event), '//');
    } else {
        return Url('/event/'.EventSlug($event), '//');
    }
}

function GroupPermission($permission = null, $groupID = null) {
    if ($groupID === null) {
        $groupID = Gdn::Controller()->Data('Group');
    }

    if (isset(Gdn::Controller()->GroupModel))
        return Gdn::Controller()->GroupModel->CheckPermission($permission, $groupID);
    $groupModel = new GroupModel();
    return $groupModel->CheckPermission($permission, $groupID);
}

function EventPermission($permission = null, $eventID = null) {
    if ($eventID === null) {
        $eventID = Gdn::Controller()->Data('Event');
    }

    if (isset(Gdn::Controller()->EventModel))
        return Gdn::Controller()->EventModel->CheckPermission($permission, $eventID);
    $eventModel = new EventModel();
    return $eventModel->CheckPermission($permission, $eventID);
}