<?php

namespace Ions\Route;

/**
 * Class RouteMatch
 * @package Ions\Route
 */
class RouteMatch
{
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var
     */
    protected $routeName;

    /**
     * RouteMatch constructor.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setRouteName($name)
    {
        $this->routeName = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function getParam($name, $default = null)
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return $default;
    }
}
