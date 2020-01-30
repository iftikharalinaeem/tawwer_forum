<?php
namespace Vanilla\Knowledge;

use Garden\Container\Reference;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Knowledge\Controllers\KbPageRoutes;
use Vanilla\Knowledge\Models\ArticleDraftCounterProvider;
use Vanilla\Knowledge\Models\KbBreadcrumbProvider;
use Vanilla\Knowledge\Models\KnowledgeTranslationResource;
use Vanilla\Knowledge\Models\KnowledgeVariablesProvider;
use Vanilla\Knowledge\Models\SearchRecordTypeArticle;
use Vanilla\Models\ThemeModel;
use Vanilla\Navigation\BreadcrumbModel;

$container = \Gdn::getContainer();

$container->rule(\Garden\Web\Dispatcher::class)
    ->addCall('addRoute', ['route' => new Reference(KbPageRoutes::class), 'kb-page'])
    ->rule(BreadcrumbModel::class)
    ->addCall('addProvider', [new Reference(KbBreadcrumbProvider::class)])
    ->rule(ThemeModel::class)
    ->addCall("addVariableProvider", [new Reference(KnowledgeVariablesProvider::class)])

    ->rule(\Vanilla\Site\SiteSectionModel::class)
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

    ->rule(SearchRecordTypeProviderInterface::class)
    ->addCall('setType', [new SearchRecordTypeArticle()])
    ->rule(\Vanilla\Contracts\Site\ApplicationProviderInterface::class)
    ->addCall('add', [new Reference(\Vanilla\Site\Application::class, ['knowledge-base', ['kb']])])

    ->rule(\Vanilla\Menu\CounterModel::class)
    ->addCall('addProvider', [new Reference(ArticleDraftCounterProvider::class)])

    ->rule(TranslationProviderInterface::class)
    ->addCall('initializeResource', [new Reference(KnowledgeTranslationResource::class)])
;
;
