<?php

namespace Ions\Router;

/**
 * Interface RouteInterface
 * @package Ions\Router
 */
interface RouteInterface
{
    /**
     * @param array $options
     * @return mixed
     */
    public static function create(array $options = []);

    /**
     * @param $route
     * @return mixed
     */
    public function match($route);
}
