<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Knowledge\Utils;

use Garden\Container\Container;
use Garden\Container\Reference;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Knowledge\Models\KnowledgeTranslationResource;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Test case with utilities for knowledge.
 */
class KbApiTestCase extends AbstractAPIv2Test {

    use KbApiTestTrait;

    protected static $addons = ['vanilla', 'translationsapi', 'knowledge'];

    /**
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container) {
        $container->rule(TranslationProviderInterface::class)
            ->addCall('initializeResource', [new Reference(KnowledgeTranslationResource::class)]);
    }
}
