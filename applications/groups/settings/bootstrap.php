<?php

function GroupSlug($Group) {
   return $Group['GroupID'].'-'.Gdn_Format::Url($Group['Name']);
}

function GroupUrl($Group, $Method = NULL) {
   if ($Method) {
      return "/group/$Method/".GroupSlug($Group);
   } else {
      return '/group/'.GroupSlug($Group);
   }
}

function GroupPermission($Permission = NULL, $GroupID = NULL) {
   if ($GroupID === NULL) {
      $GroupID = Gdn::Controller()->Data('Group.GroupID');
   }
   
   if (isset(Gdn::Controller()->GroupModel))
      return Gdn::Controller()->GroupModel->CheckPermission($Permission, $GroupID);
   $GroupModel = new GroupModel();
   return $GroupModel->CheckPermission($Permission, $GroupID);
}