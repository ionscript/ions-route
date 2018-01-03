<?php

namespace Ions\Route;

/**
 * Interface RouteInterface
 * @package Ions\Route
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
