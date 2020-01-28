<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\EventManager;
use Vanilla\Webhooks\Library\EventDispatcher;

/** @var EventManager */
$eventManager = Gdn::getContainer()->get(EventManager::class);
$eventManager->addListenerMethod(EventDispatcher::class, "dispatch");
