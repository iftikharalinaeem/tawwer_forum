<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * @param $Group
 * @return string
 */
function GroupSlug($Group) {
    return $Group['GroupID'].'-'.Gdn_Format::Url($Group['Name']);
}

function GroupUrl($Group, $Method = null, $WithDomain = '//') {
    if ($Method) {
        return Url("/group/$Method/".GroupSlug($Group), $WithDomain);
    } else {
        return Url('/group/'.GroupSlug($Group), $WithDomain);
    }
}
function EventSlug($Event) {
    return $Event['EventID'].'-'.Gdn_Format::Url($Event['Name']);
}

function EventUrl($Event, $Method = null) {
    if ($Method) {
        return Url("/event/$Method/".EventSlug($Event), '//');
    } else {
        return Url('/event/'.EventSlug($Event), '//');
    }
}

function GroupPermission($Permission = null, $GroupID = null) {
    if ($GroupID === null) {
        $GroupID = Gdn::Controller()->Data('Group');
    }

    if (isset(Gdn::Controller()->GroupModel))
        return Gdn::Controller()->GroupModel->CheckPermission($Permission, $GroupID);
    $GroupModel = new GroupModel();
    return $GroupModel->CheckPermission($Permission, $GroupID);
}

function EventPermission($Permission = null, $EventID = null) {
    if ($EventID === null) {
        $EventID = Gdn::Controller()->Data('Event');
    }

    if (isset(Gdn::Controller()->EventModel))
        return Gdn::Controller()->EventModel->CheckPermission($Permission, $EventID);
    $EventModel = new EventModel();
    return $EventModel->CheckPermission($Permission, $EventID);
}