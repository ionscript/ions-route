<?php

namespace Ions\Route;

use Ions\Std\PriorityList;

/**
 * Class Router
 * @package Ions\Route
 */
class Router
{
    /**
     * @var PriorityList
     */
    protected $routes;

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->routes = new PriorityList();
    }

    /**
     * @param array $options
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function create(array $options = [])
    {
        if (!is_array($options)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects an array set of options',
                __METHOD__
            ));
        }

        $instance = new static();

        if ($options) {
            $instance->addRoutes($options);
        }

        return $instance;
    }

    /**
     * @param $routes
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addRoutes($routes)
    {
        if (!is_array($routes)) {
            throw new \InvalidArgumentException('addRoutes expects an array set of routes');
        }

        foreach ($routes as $name => $route) {
            $this->addRoute($name, $route);
        }

        return $this;
    }

    /**
     * @param $name
     * @param $route
     * @param null $priority
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addRoute($name, $route, $priority = null)
    {
        if (!$route instanceof RouteInterface) {
            $route = Route::create($route);
        }

        if ($priority === null && isset($route->priority)) {
            $priority = $route->priority;
        }

        $this->routes->insert($name, $route, $priority);

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function removeRoute($name)
    {
        $this->routes->remove($name);
        return $this;
    }

    /**
     * @param $routes
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setRoutes($routes)
    {
        $this->routes->clear();
        $this->addRoutes($routes);
        return $this;
    }

    /**
     * @return PriorityList
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasRoute($name)
    {
        return $this->routes->get($name) !== null;
    }

    /**
     * @param $name
     * @return null
     */
    public function getRoute($name)
    {
        return $this->routes->get($name);
    }

    /**
     * @param $path
     * @return null
     */
    public function match($path)
    {
        foreach ($this->routes as $name => $route) {
            if (($match = $route->match($path)) instanceof RouteMatch) {
                $match->setRouteName($name);
                return $match;
            }
        }

        return null;
    }
}
