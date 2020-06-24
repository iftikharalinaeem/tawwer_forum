<?php

use Vanilla\TranslationsApi\Models\TranslationProvider;

$container = \Gdn::getContainer();

$container->rule(\Vanilla\Site\TranslationModel::class)
->addCall('addProvider', [new \Garden\Container\Reference(TranslationProvider::class)])
;
