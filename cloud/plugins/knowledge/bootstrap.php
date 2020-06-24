<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Reference;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\ArticleRevisionModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Knowledge\Models\KnowledgeTranslationResource;
use Vanilla\Knowledge\Models\NavigationCacheProcessor;
use Vanilla\Theme\ThemeSectionModel;
use Vanilla\TranslationsApi\Models\TranslationPropertyModel;

$container = \Gdn::getContainer();

$container->rule(\Vanilla\Site\SiteSectionModel::class)
    ->addCall(
        'addDefaultRoute',
        [
            'Knowledge Base',
            [
                'Destination' => 'kb',
                'Type' => 'Internal',
                'ImageUrl' => 'plugins/knowledge/kb.png'
            ]
        ]
    )
    ->addCall(
        'registerApplication',
        [
            'knowledgeBase',
            ['name' => 'Knowledge Base']
        ]
    )
    ->rule(TranslationProviderInterface::class)
    ->addCall('initializeResource', [new Reference(KnowledgeTranslationResource::class)])
    ->rule(ArticleModel::class)
    ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
    ->rule(ArticleRevisionModel::class)
    ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
    ->rule(KnowledgeCategoryModel::class)
    ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
    ->rule(KnowledgeBaseModel::class)
    ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
    ->rule(TranslationPropertyModel::class) // If the plugins not enabled it doesn't matter, since just a rule.
    ->addCall('addPipelineProcessor', [new Reference(NavigationCacheProcessor::class)])
;

$container->rule(ThemeSectionModel::class)
    ->addCall('registerModernSection', ['Knowledge Base']);
