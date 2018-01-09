<?php

namespace Ions\Route;

use Ions\Std\RequestInterface;

/**
 * Class Route
 * @package Ions\Route
 */
class Route implements RouteInterface
{
    /**
     * @var array
     */
    protected static $cacheEncode = [];

    /**
     * @var array
     */
    protected static $urlEncode = [
        '%21' => '!',
        '%24' => '$',
        '%26' => '&',
        '%27' => "'",
        '%28' => '(',
        '%29' => ')',
        '%2A' => '*',
        '%2B' => '+',
        '%2C' => ',',
        '%3A' => ':',
        '%3B' => ';',
        '%3D' => '=',
        '%40' => '@',
    ];

    /**
     * @var array
     */
    protected $parts;

    /**
     * @var string
     */
    protected $regex;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $defaults;

    /**
     * Route constructor.
     * @param $route
     * @param array $constraints
     * @param array $defaults
     */
    public function __construct($route, array $constraints = [], array $defaults = [])
    {
        $this->defaults = $defaults;
        $this->parts = $this->parse($route);
        $this->regex = $this->regex($this->parts, $constraints);
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

        if (!isset($options['route'])) {
            throw new \InvalidArgumentException('Missing "route" in options array');
        }

        if (!isset($options['constraints'])) {
            $options['constraints'] = [];
        }

        if (!isset($options['defaults'])) {
            $options['defaults'] = [];
        }

        return new static($options['route'], $options['constraints'], $options['defaults']);
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param array $defaults
     * @return void
     */
    public function setDefaults($defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * @param $route
     * @return array
     * @throws \RuntimeException
     */
    protected function parse($route)
    {
        $currentPos = 0;
        $length = strlen($route);
        $parts = [];
        $levelParts = [&$parts];
        $level = 0;

        while ($currentPos < $length) {
            preg_match('(\G(?P<literal>[^:{\[\]]*)(?P<token>[:{\[\]]|$))', $route, $matches, 0, $currentPos);

            $currentPos += strlen($matches[0]);

            if (!empty($matches['literal'])) {
                $levelParts[$level][] = ['literal', $matches['literal']];
            }

            if ($matches['token'] === ':') {
                if (!preg_match(
                    '(\G(?P<name>[^:/{\[\]]+)(?:{(?P<delimiters>[^}]+)})?:?)',
                    $route,
                    $matches,
                    0,
                    $currentPos
                )
                ) {
                    throw new \RuntimeException('Found empty parameter name');
                }

                $levelParts[$level][] = [
                    'parameter',
                    $matches['name'],
                    isset($matches['delimiters']) ? $matches['delimiters'] : null
                ];

                $currentPos += strlen($matches[0]);
            } elseif ($matches['token'] === '{') {
                if (!preg_match('(\G(?P<literal>[^}]+)\})', $route, $matches, 0, $currentPos)) {
                    throw new \RuntimeException('Translated literal missing closing bracket');
                }

                $currentPos += strlen($matches[0]);

                $levelParts[$level][] = ['translated-literal', $matches['literal']];
            } elseif ($matches['token'] === '[') {
                $levelParts[$level][] = ['optional', []];
                $levelParts[$level + 1] = &$levelParts[$level][count($levelParts[$level]) - 1][1];

                $level++;
            } elseif ($matches['token'] === ']') {
                unset($levelParts[$level]);
                $level--;

                if ($level < 0) {
                    throw new \RuntimeException('Found closing bracket without matching opening bracket');
                }
            } else {
                break;
            }
        }

        if ($level > 0) {
            throw new \RuntimeException('Found unbalanced brackets');
        }

        return $parts;
    }

    /**
     * @param array $parts
     * @param array $constraints
     * @param int $groupIndex
     * @return string
     */
    protected function regex(array $parts, array $constraints, &$groupIndex = 1)
    {
        $regex = '';

        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $regex .= preg_quote($part[1], '/');
                    break;

                case 'parameter':
                    $groupName = '?P<param' . $groupIndex . '>';

                    if (isset($constraints[$part[1]])) {
                        $regex .= '(' . $groupName . $constraints[$part[1]] . ')';
                    } elseif ($part[2] === null) {
                        $regex .= '(' . $groupName . '[^/]+)';
                    } else {
                        $regex .= '(' . $groupName . '[^' . $part[2] . ']+)';
                    }

                    $this->params['param' . $groupIndex++] = $part[1];
                    break;

                case 'optional':
                    $regex .= '(?:' . $this->regex($part[1], $constraints, $groupIndex) . ')?';
                    break;
            }
        }

        return $regex;
    }

    /**
     * @param $route
     * @return RouteMatch|null
     */
    public function match($route)
    {
        $regex = $this->regex;

        if($route instanceof RequestInterface) {
            $route = $route->getRequestUri();

            if (preg_match('#^(?P<route>.+)\?#', $route, $matches)) {
                $route = $matches['route'];
            }
        }

        if(!is_string($route)) {
            return null;
        }

        $result = preg_match('(^' . $regex . '$)', $route, $matches);

        if (!$result) {
            return null;
        }

        $params = [];

        foreach ($this->params as $index => $name) {
            if (isset($matches[$index]) && $matches[$index] !== '') {
                $params[$name] = $this->decode($matches[$index]);
            }
        }

        return new RouteMatch(array_merge($this->defaults, $params));
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function encode($value)
    {
        $key = (string)$value;
        if (!isset(static::$cacheEncode[$key])) {
            static::$cacheEncode[$key] = rawurlencode($value);
            static::$cacheEncode[$key] = strtr(static::$cacheEncode[$key], static::$urlEncode);
        }
        return static::$cacheEncode[$key];
    }

    /**
     * @param $value
     * @return string
     */
    protected function decode($value)
    {
        return rawurldecode($value);
    }
}
