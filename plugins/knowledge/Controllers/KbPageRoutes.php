<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers;

use Garden\ClassLocator;
use Garden\Web\ResourceRoute;
use Interop\Container\ContainerInterface;

/**
 * Resource route for matching page routes for the /kb directory.
 */
class KbPageRoutes extends ResourceRoute {
    const BASE_PATH = '/kb/';
    const PATTERN = '*\\Knowledge\\Controllers\\%sPageController';

    /**
     * Override constructor to take care of the first few arguments.
     *
     * @param ContainerInterface|null $container The container for ResourceRoute.
     * @param ClassLocator|null $classLocator A class locator for ResourceRoute.
     */
    public function __construct(ContainerInterface $container = null, ClassLocator $classLocator = null) {
        parent::__construct(
            self::BASE_PATH,
            self::PATTERN,
            $container,
            $classLocator
        );
        $this->setMeta('CONTENT_TYPE', 'text/html; charset=utf-8');
        $this->setRootController(KbRootController::class);
    }
}
