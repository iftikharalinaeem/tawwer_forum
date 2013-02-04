<?php

function GroupSlug($Group) {
   return $Group['GroupID'].'-'.Gdn_Format::Url($Group['Name']);
}

function GroupUrl($Group, $Method = NULL) {
   if ($Method) {
      return Url("/group/$Method/".GroupSlug($Group));
   } else {
      return Url('/group/'.GroupSlug($Group));
   }
}
function EventSlug($Event) {
   return $Event['EventID'].'-'.Gdn_Format::Url($Event['Name']);
}

function EventUrl($Event) {
   return '/event/'.EventSlug($Event);
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