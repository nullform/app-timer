<?php

namespace Nullform\AppTimer;

/**
 * Timer interval.
 *
 * @package Nullform\AppTimer
 */
class Interval
{
    /**
     * Unique interval ID.
     *
     * @var string
     */
    public $id = "";

    /**
     * Interval description.
     *
     * @var string
     */
    public $description = "";

    /**
     * Start time. Unix timestamp with microseconds.
     *
     * @var int
     */
    public $start = 0;

    /**
     * End time. Unix timestamp with microseconds.
     *
     * @var int
     */
    public $end = 0;

    /**
     * Duration.
     *
     * @var int
     */
    public $duration = 0;

    /**
     * ID of parent interval.
     *
     * @var string
     */
    public $parent = "";

    /**
     * Child (nested) intervals.
     *
     * @var Interval[]
     */
    public $children = [];

    /**
     * Additional information.
     *
     * @var Extras
     */
    private $extras;


    /**
     * Interval constructor.
     *
     * @param string $description
     */
    public function __construct(string $description)
    {
        $this->id = \uniqid();
        $this->description = $description;
        if (empty($this->description)) {
            $this->description = "Interval #" . $this->id;
        }
    }

    /**
     * Additional information.
     *
     * @return Extras
     */
    public function extras(): Extras
    {
        if (!($this->extras instanceof Extras)) {
            $this->extras = new Extras();
        }

        return $this->extras;
    }
}