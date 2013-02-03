<?php

function GroupSlug($Group) {
   return $Group['GroupID'].'-'.Gdn_Format::Url($Group['Name']);
}

function GroupUrl($Group) {
   return '/group/'.GroupSlug($Group);
}

function EventSlug($Event) {
   return $Event['EventID'].'-'.Gdn_Format::Url($Event['Name']);
}

function EventUrl($Event) {
   return '/event/'.EventSlug($Event);
}