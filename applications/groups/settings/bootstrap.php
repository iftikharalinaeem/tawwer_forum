<?php

function GroupSlug($Group) {
   return $Group['GroupID'].'-'.Gdn_Format::Url($Group['Name']);
}

function GroupUrl($Group) {
   return '/group/'.GroupSlug($Group);
}
