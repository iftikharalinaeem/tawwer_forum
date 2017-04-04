<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Swagger\Models;

use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Web\ResourceRoute;
use Garden\Web\Route;
use ReflectionMethod;

class ReflectionAction {
    /**
     * @var ReflectionMethod
     */
    private $method;

    /**
     * @var EventManager
     */
    private $events;

    /**
     * @var ResourceRoute $route
     */
    private $route;

    /**
     * @var string The name of the resource (the controller expressed as a path).
     */
    private $resource;

    /**
     * @var string
     */
    private $subpath;

    /**
     * @var string The http method of the resource.
     */
    private $httpMethod;

    /**
     * @var string
     */
    private $idParam;

    /**
     * @var string;
     */
    private $bodyParam;

    /**
     * @var string[]
     */
    private $params;

    /**
     * @var array
     */
    private $args;

    /**
     * @var object A controller instance.
     */
    private $instance;

    /**
     * @var array
     */
    private $operation;

    public function __construct(ReflectionMethod $method, $instance, ResourceRoute $route, EventManager $events) {
        $this->method = $method;
        $this->events = $events;
        $this->route = $route;
        $this->instance = $instance;

        $this->reflectAction();
    }

    /**
     * Reflect a controller action from a callback.
     *
     * @return null
     */
    private function reflectAction() {
        $method = $this->method;
        $resourceRegex = str_replace('%s', '([a-z][a-z0-9]*)', $this->route->getControllerPattern());

        // Regex the method name against event handler syntax or regular method syntax.
        if (preg_match(
            "`^(?:(?<class>$resourceRegex)_)?(?<method>get|post|patch|put|options|delete|index)(?:_(?<path>[a-z0-9]+?))?$`i",
            $method->getName(),
            $m
        )) {
            $controller = $m['class'] ?: $method->getDeclaringClass()->getName();
            $httpMethod = $m['method'];
            $subpath = isset($m['path']) ? $m['path'] : '';
        } else {
            throw new \InvalidArgumentException("The method name does not match an action's pattern", 500);
        }

        if (strcasecmp($httpMethod, 'index') === 0) {
            $httpMethod = 'GET';
            $subpath = '';
        }

        // Check against the controller pattern.
        if (preg_match("`^$resourceRegex$`i", $controller, $m)) {
            $resource = $m[1];
        } else {
            throw new \InvalidArgumentException("The controller is not an API controller.", 500);
        }

        $this->httpMethod = strtoupper($httpMethod);
        $this->resource = $this->dashCase($resource);
        $this->subpath = ltrim('/'.$this->dashCase($subpath), '/');

        $this->args = [];
        foreach ($method->getParameters() as $param) {
            // Default the call args.
            $this->args[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : ($param->isArray() ? [] : null);

            $p = null;
            if ($this->route->isMapped($param, Route::MAP_BODY)) {
                $this->bodyParam = $param->getName();
                $p = ['name' => $param->getName(), 'in' => 'body', 'required' => true];
            } elseif (!$param->getClass() && !$this->route->isMapped($param)) {
                $p = ['name' => $param->getName(), 'in' => 'path', 'required' => true];

                $constraint = (array)$this->route->getConstraint($param->getName()) + ['position' => '*'];
                if ($param->getPosition() === 0 && $constraint['position'] === 0) {
                    $this->idParam = $param->getName();
                }
            }

            if ($p !== null) {
                if ($param->isDefaultValueAvailable()) {
                    $p['default'] = $param->getDefaultValue();
                }

                $this->params[$p['name']] = $p;
            }
        }
    }

    public function getOperation() {
        if ($this->operation === null) {
            $this->operation = $this->makeOperation();
        }
        return $this->operation;
    }

    /**
     * Return the Swagger operation array.
     */
    private function makeOperation() {
        /* @var Schema $in, $allIn */
        /* @var Schema $out */
        /* @var Schema $allIn */
        $in = $out = $allIn = null;

        // Set up an event handler that will capture the schemas.
        $fn  = function ($controller, Schema $schema, $type) use (&$in, &$out, &$allIn) {
            switch ($type) {
                case 'in':
                    if (empty($this->bodyParam)) {
                        if ($allIn instanceof Schema) {
                            $allIn = $allIn->merge($schema);
                        } else {
                            $allIn = $schema;
                        }
                    } elseif ($in !== null) {
                        $allIn = $in;
                    }
                    $in = $schema;
                    break;
                case 'out':
                    $out = $schema;
                    throw new ShortCircuitException();
            }
        };

        try {
            $this->events->bind('controller_schema', $fn, EventManager::PRIORITY_LOW);

            $r = $this->method->invoke($this->instance, ...array_values($this->args));

        } catch (ShortCircuitException $ex) {
            // We should have everything we need now.
        } finally {
            $this->events->unbind('controller_schema', $fn);
        }

        // Fill in information about the parameters from the input schema.
        $summary = '';
        if ($in instanceof Schema) {
            $summary = $in->getDescription();
            $inArr = $in->jsonSerialize();
            $allInArr = $allIn !== null ? $allIn->jsonSerialize() : [];
            unset($inArr['description']);

            if (!empty($this->bodyParam)) {
                $this->params[$this->bodyParam]['schema'] = $inArr;
                /* @var array $property */
                foreach ($allInArr['properties'] as $name => $property) {
                    if (isset($this->params[$name])) {
                        $this->params[$name] = (array)$this->params[$name] + (array)$property;
                    }
                }
            } else {
                /* @var array $property */
                foreach ($allInArr['properties'] as $name => $property) {
                    if (isset($this->params[$name])) {
                        $this->params[$name] = (array)$this->params[$name] + (array)$property;
                    } else {
                        $this->params[$name] = ['name' => $name, 'in' => 'query'] + $property;
                    }
                }
            }
        }

        // Make sure the parameters have a type now.
        foreach ($this->params as $name => &$param) {
            if ($param['in'] === 'path') {
                $param += ['type' => $name === $this->idParam ? 'integer' : 'string'];
            }
        }

        // Fill in the responses.
        $responses = [];
        if ($out instanceof Schema && !empty($out->getSchemaArray())) {
            $status = $this->httpMethod === 'POST' && empty($this->idParam) ? '201' : '200';

            $responses[$status]['description'] = $out->getDescription() ?: 'Success';
            $responses[$status]['schema'] = $out->jsonSerialize();
        } else {
            $status = $this->httpMethod === 'POST' && empty($this->idParam) ? '201' : '204';
            $responses[$status]['description'] = 'Success';
        }

        $r = [
            'tags' => [ucfirst($this->resource)],
            'summary' => $summary,
            'parameters' => array_values($this->params),
            'responses' => $responses
        ];

        return array_filter($r);
    }

    private function dashCase($class) {
        $class = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', '-$1', $class);
        $class = preg_replace('`([A-Z0-9])(?=[a-z])`', '-$1', $class);
        $class = trim($class, '-');
        return strtolower($class);
    }

    /**
     * Get the httpMethod.
     *
     * @return string Returns the httpMethod.
     */
    public function getHttpMethod() {
        return $this->httpMethod;
    }

    /**
     * @return string
     */
    public function getPath() {
        $r = '/'.$this->resource.
            ($this->idParam ? '/{'.$this->idParam.'}' : '').
            (empty($this->subpath) ? '' : '/'.$this->subpath);

        foreach ($this->params as $key => $param) {
            if ($param['in'] === 'path' && $key !== $this->idParam) {
                $r .= '/{'.$key.'}';
            }
        }

        return $r;
    }
}
