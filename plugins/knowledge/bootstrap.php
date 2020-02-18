<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Reference;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Knowledge\Models\KnowledgeTranslationResource;

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
;
