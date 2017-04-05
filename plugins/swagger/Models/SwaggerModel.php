<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Swagger\Models;

use Garden\EventManager;
use Garden\Web\Dispatcher;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Garden\Web\ResourceRoute;
use Interop\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use Vanilla\AddonManager;

/**
 * Handles the swagger JSON commands.
 *
 * Note this isn't a real Vanilla model.
 */
class SwaggerModel {
    private static $httpMethods = ['get', 'post', 'patch', 'put', 'options', 'delete'];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AddonManager
     */
    private $addonManager;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var ResourceRoute
     */
    private $route;

    /**
     * @var EventManager
     */
    private $events;

    /**
     * @var ContainerInterface The container used to create controllers.
     */
    private $container;

    /**
     * Construct a {@link SwaggerModel}.
     */
    public function __construct(
        RequestInterface $request,
        AddonManager $addonManager,
        EventManager $events,
        Dispatcher $dispatcher,
        ContainerInterface $container
    ) {
        $this->request = $request;
        $this->addonManager = $addonManager;
        $this->dispatcher = $dispatcher;
        $this->route = $this->dispatcher->getRoute('api-v2');
        $this->events = $events;
        $this->container = $container;
    }

    /**
     * Get the root node of the swagger application.
     *
     * @return array Returns the root node.
     */
    public function getSwaggerObject() {
        if ($this->route === null) {
            throw new ServerException('Could not find the APIv2 router.', 500);
        }

        $r = [
            'swagger' => '2.0',
            'info' => [
                'title' => 'Vanilla API',
                'description' => 'API access to your community.',
                'version' => '2.0-alpha'
            ],
            'host' => $this->request->getHost(),
            'basePath' => $this->request->getRoot().'/api/v2',
            'paths' => [],

        ];

        foreach ($this->getActions() as $action) {
            $r['paths'][$action->getPath()][strtolower($action->getHttpMethod())] = $action->getOperation();
        }

        $r = $this->gatherDefinitions($r);

        return $r;
    }

    /**
     * @return \Generator|ReflectionAction[]
     */
    private function getActions() {
        $controllers = $this->addonManager->findClasses('*\\*ApiController');
        sort($controllers);

        foreach ($controllers as $controller) {
            if ($controller === \SwaggerApiController::class) {
                continue;
            }

            $class = new ReflectionClass($controller);
            if (!$class->isInstantiable()) {
                continue;
            }

            $instance = $this->container->get($controller);
            $actions = iterator_to_array($this->getControllerActions($class, $instance));

            usort($actions, function (ReflectionAction $a, ReflectionAction $b) {
                $cmp1 = strcasecmp($a->getPath(), $b->getPath());
                if ($cmp1 !== 0) {
                    return $cmp1;
                }

                $cmpa = array_search(strtolower($a->getHttpMethod()), static::$httpMethods);
                $cmpb = array_search(strtolower($b->getHttpMethod()), static::$httpMethods);

                if ($cmpa !== false && $cmpb !== false) {
                    return strnatcmp($cmpa, $cmpb);
                } elseif ($cmpa !== false) {
                    return -1;
                } elseif ($cmpb !== false) {
                    return 1;
                } else {
                    return strcmp($a->getHttpMethod(), $b->getHttpMethod());
                }

            });

            foreach ($actions as $action) {
                yield $action;
            }
        }
    }

    private function gatherDefinitions(array $arr) {
        $definitions = [];

        $fn = function (array $arr) use (&$definitions, &$fn) {
            $result = $arr;

            if ($arr['id'] === 'DiscussionPost') {
                $foo = 'bar';
            }

            foreach ($result as $key => &$value) {
                if (is_array($value)) {
                    $value = $fn($value);
                }
            }

            if (isset($result['type'], $result['id'])) {
                $id = $result['id'];
                unset($result['id']);

                $definitions[$id] = $result;
                return ['$ref' => "#/definitions/$id"];
            }
            return $result;
        };

        $result = $fn($arr);

        if (!empty($definitions)) {
            ksort($definitions);
            $result['definitions'] = $definitions;
        }
        return $result;
    }

    /**
     * Get all of the actions for a controller.
     *
     * @param ReflectionClass $controller The controller class to reflect.
     * @return \Generator|ReflectionAction[] Yields the actions for the controller.
     */
    private function getControllerActions(ReflectionClass $controller, $instance) {
        foreach ($controller->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isAbstract() || $method->isStatic() || $method->getName()[0] === '_') {
                continue;
            }

            try {
                $action = new ReflectionAction($method, $instance, $this->route, $this->events);

                yield $action;
            } catch (\InvalidArgumentException $ex) {
                continue;
            }
        }
    }
}
