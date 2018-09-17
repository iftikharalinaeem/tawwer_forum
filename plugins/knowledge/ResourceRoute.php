<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Knowledge;

use Garden\ClassLocator;
use Garden\ControllerActionAutodetectInterface;
use Interop\Container\ContainerInterface;

/**
 * Maps requests to controllers using RESTful URLs.
 *
 * Here are some of the features of this route.
 *
 * - You can attach the route to base path. (ex. /api/controller maps to ApiController).
 * - You can customize the naming scheme of the controller with a controller pattern.
 * - Add parameter constraints to help clean data and disambiguate between different endpoints.
 * - Controllers can be created through an optional dependency injection container.
 * - Class and method lookup can be customized.
 * - Supports different controller methods for different HTTP methods.
 */
class ResourceRoute extends \Garden\Web\ResourceRoute {

    /**
     * Find the method call for a controller.
     *
     * @param object $controller The controller to find the method for.
     * @param RequestInterface $request The request being routed.
     * @param array $pathArgs The current path arguments from the request.
     * @return Action|null Returns method call information or **null** if there is no method.
     */
    private function findAction($controller, RequestInterface $request, array $pathArgs) {
        if ($controller instanceof ControllerActionAutodetectInterface) {
            $methodName = $controller->detectAction($request, $pathArgs);
            if ($callback = $this->classLocator->findMethod($controller, $methodName)) {
                $args = $pathArgs;
                $method = $this->reflectCallback($callback);
                $callbackArgs = $this->matchArgs($method, $request, $args, $controller);

                if ($callbackArgs !== null) {
                    $result = new Action($callback, $callbackArgs);
                    $result->setMeta('method', $request->getMethod());
                    $result->setMeta('action', $result->getCallback()[1]);
                    return $result;
                }
            }
        };
        $methodNames = $this->getControllerMethodNames($request->getMethod(), $pathArgs);
        foreach ($methodNames as list($methodName, $omit)) {
            if ($callback = $this->findMethod($controller, $methodName)) {
                $args = $pathArgs;
                if ($omit !== null) {
                    unset($args[$omit]);
                }
                $method = $this->reflectCallback($callback);

                if (!$this->checkMethodCase($method->getName(), $methodName, $controller, true)) {
                    continue;
                }

                $callbackArgs = $this->matchArgs($method, $request, $args, $controller);

                if ($callbackArgs !== null) {
                    $result = new Action($callback, $callbackArgs);
                    $result->setMeta('method', $request->getMethod());
                    $result->setMeta('action', $result->getCallback()[1]);
                    return $result;
                }
            }
        }
        return null;
    }

}
