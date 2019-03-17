<?php

namespace Nullform\AppTimer;

/**
 * Additional information.
 *
 * @package Nullform\AppTimer
 */
class Extras
{
    /**
     * Additional information (associative array width [string => string] pairs).
     *
     * @var array
     */
    private $extras = [];

    /**
     * Add element to additional information array.
     *
     * @param string $key
     * @param string $value
     * @return int Current number of elements in the array with additional information.
     */
    public function add(string $key, string $value): int
    {
        $this->extras[$key] = $value;

        return \count($this->extras);
    }

    /**
     * Remove element from additional information array.
     *
     * @param string $key
     * @return int Current number of elements in the array with additional information.
     */
    public function remove(string $key): int
    {
        if (isset($this->extras[$key])) {
            unset($this->extras[$key]);
        }

        return \count($this->extras);
    }

    /**
     * Additional information (associative array width [string => string] pairs).
     *
     * @return array
     * @uses Interval::$extras
     */
    public function get(): array
    {
        return $this->extras;
    }
}