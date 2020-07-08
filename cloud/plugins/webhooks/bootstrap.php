<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\EventManager;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Webhooks\Library\EventDispatcher;
use Vanilla\Webhooks\Library\EventScheduler;

$container = Gdn::getContainer();

/** @var AddonManager */
$addonManager = $container->get(AddonManager::class);
$hostedQueueAvailable = $addonManager->isEnabled("hosted-job", Addon::TYPE_ADDON);
$container->rule(EventScheduler::class)
    ->setShared(true)
    ->addCall("useHostedQueue", [$hostedQueueAvailable]);

$container->rule(EventDispatcher::class)->setShared(true);

/** @var EventManager */
$eventManager = Gdn::getContainer()->get(EventManager::class);
$eventManager->addListenerMethod(EventDispatcher::class, "dispatch");
