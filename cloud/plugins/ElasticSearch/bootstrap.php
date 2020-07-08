<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\EventManager;
use Vanilla\Cloud\ElasticSearch\ElasticEventHandler;

$container = \Gdn::getContainer();
/** @var EventManager */
$eventManager =$container->get(EventManager::class);
$eventManager->addListenerMethod(ElasticEventHandler::class, 'handleResourceEvent');
