<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Search\SearchService;
use Vanilla\Sphinx\Search\SphinxSearchDriver;

$dic = Gdn::getContainer();

$dic->rule(SearchService::class)
    ->addCall('registerActiveDriver', [new \Garden\Container\Reference(SphinxSearchDriver::class)]);
