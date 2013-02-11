<?php

function GroupSlug($Group) {
   return $Group['GroupID'].'-'.Gdn_Format::Url($Group['Name']);
}

function GroupUrl($Group, $Method = NULL) {
   if ($Method) {
      return Url("/group/$Method/".GroupSlug($Group), '//');
   } else {
      return Url('/group/'.GroupSlug($Group), '//');
   }
}
function EventSlug($Event) {
   return $Event['EventID'].'-'.Gdn_Format::Url($Event['Name']);
}

function EventUrl($Event, $Method = NULL) {
   if ($Method) {
      return Url("/event/$Method/".EventSlug($Event), '//');
   } else {
      return Url('/event/'.EventSlug($Event), '//');
   }
}

function GroupPermission($Permission = NULL, $GroupID = NULL) {
   if ($GroupID === NULL) {
      $GroupID = Gdn::Controller()->Data('Group');
   }
   
   if (isset(Gdn::Controller()->GroupModel))
      return Gdn::Controller()->GroupModel->CheckPermission($Permission, $GroupID);
   $GroupModel = new GroupModel();
   return $GroupModel->CheckPermission($Permission, $GroupID);
}

function EventPermission($Permission = NULL, $EventID = NULL) {
   if ($EventID === NULL) {
      $EventID = Gdn::Controller()->Data('Event');
   }
   
   if (isset(Gdn::Controller()->EventModel))
      return Gdn::Controller()->EventModel->CheckPermission($Permission, $EventID);
   $EventModel = new EventModel();
   return $EventModel->CheckPermission($Permission, $EventID);
}