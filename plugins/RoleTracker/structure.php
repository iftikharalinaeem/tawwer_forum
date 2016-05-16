<?php

Gdn::structure()->table('Role')
    ->column('IsTracked', 'tinyint(1)', 0)
    ->column('TrackerTagID', 'tinyint(1)', true)
    ->set(false, false);
