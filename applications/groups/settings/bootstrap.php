<?php

function GroupUrl($Group) {
   return '/group/'.$Group['GroupID'].'-'.Gdn_Format::Url($Group['Name']);
}
