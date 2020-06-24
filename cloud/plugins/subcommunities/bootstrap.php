<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Addons\Subcommunities;

use Vanilla\Subcommunities\Models\MultisiteReduxPreloader;
use Vanilla\Subcommunities\Models\SubcomunitiesSiteSectionProvider;
use \Garden\Container\Reference;
use Vanilla\Web\Page;
use \Gdn_Controller;

$dic = \Gdn::getContainer();

$providerArgs = ['provider' => new Reference(MultisiteReduxPreloader::class)];
$dic
    ->rule(Page::class)
    ->setInherit(true)
    ->addCall('registerReduxActionProvider', $providerArgs)
    ->rule(Gdn_Controller::class)
    ->setInherit(true)
    ->addCall('registerReduxActionProvider', $providerArgs)
    ->rule(\Vanilla\Site\SiteSectionModel::class)
    ->addCall('addProvider', [new Reference(SubcomunitiesSiteSectionProvider::class)])
;
