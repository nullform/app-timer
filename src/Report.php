<?php

namespace Nullform\AppTimer;

/**
 * Timer report.
 *
 * @package Nullform\AppTimer
 */
class Report
{
    /**
     * Timer description.
     *
     * @var string
     */
    public $description = "";

    /**
     * Timer start time (YYYY-MM-DD hh:mm:ss).
     *
     * @var string
     */
    public $time = "";

    /**
     * Client IP address.
     *
     * @var string
     */
    public $ip_address = "";

    /**
     * Request URI.
     *
     * @var string
     */
    public $uri = "";

    /**
     * Server API.
     *
     * @var string
     * @see \php_sapi_name()
     */
    public $sapi = "";

    /**
     * Request method.
     *
     * @var string
     */
    public $http_method = "";

    /**
     * All request params.
     *
     * @var array
     */
    public $request_params = [];

    /**
     * The total duration of all intervals.
     *
     * @var int
     */
    public $duration = 0;

    /**
     * The total number of measurements during the session.
     *
     * @var int
     */
    public $intervals_count = 0;

    /**
     * Intervals.
     *
     * @var Interval[]
     */
    public $intervals = [];

    /**
     * Additional information.
     *
     * @var Extras
     */
    private $extras;


    /**
     * Set all intervals to report.
     *
     * @param Interval[] $intervals
     */
    public function setIntervals(array $intervals): void
    {
        $this->duration = 0;
        $this->intervals_count = 0;

        $this->intervals = $intervals;

        /**
         * @param Interval $interval
         * @param Report $report
         */
        $intervals_counter = function ($interval, $report) use (&$intervals_counter) {

            $report->intervals_count++;

            if (!empty($interval->children)) {
                foreach ($interval->children as $_child) {
                    $intervals_counter($_child, $report);
                }
            }

        };

        if (!empty($this->intervals)) {

            foreach ($this->intervals as $_interval) {

                $intervals_counter($_interval, $this);

                $this->duration += $_interval->duration;

            }

        }
    }

    /**
     * Longest interval from all.
     *
     * @return Interval|null
     */
    public function longestInterval(): ?Interval
    {
        /**
         * @var Interval|null $longest_interval
         */
        $longest_interval = null;
        /**
         * @var Interval[] $all_intervals
         */
        $all_intervals = [];
        /**
         * @var int $max_duration
         */
        $max_duration = 0;

        /**
         * @param Interval[] $intervals
         */
        $interval_search = function (array $intervals) use (&$all_intervals, &$interval_search) {
            $all_intervals = \array_merge($all_intervals, $intervals);
            foreach ($intervals as $_interval) {
                if (!empty($_interval->children)) {
                    $interval_search($_interval->children);
                }
            }
        };

        if (!empty($this->intervals)) {
            $interval_search($this->intervals);
        }

        if (!empty($all_intervals)) {
            foreach ($all_intervals as $_interval) {
                if ($_interval->duration > $max_duration) {
                    $longest_interval = $_interval;
                    $max_duration = $_interval->duration;
                }
            }
        }

        return $longest_interval;
    }

    /**
     * Report as human-readable string.
     *
     * @return string
     */
    public function toString(): string
    {
        $sep = PHP_EOL;
        $report_items = "";
        $report = "";

        $report .= "[" . $this->time . "] ";
        if (!empty($this->http_method) && !empty($this->uri)) {
            $report .= \strtoupper($this->http_method) . " " . $this->uri;
        }
        $report .= $sep;

        if (!empty($this->request_params) && \is_array($this->request_params)) {
            $report .= "Params: " . $sep;
            $report .= $this->JSONEncode($this->request_params) . $sep;
        }

        if (!empty($this->ip_address)) {
            $report .= "IP address: " . $this->ip_address . $sep;
        }

        if (!empty($this->description)) {
            $report .= "Description: " . $this->description . $sep;
        }

        if ($this->extras()->get()) {
            foreach ($this->extras()->get() as $_key => $_value) {
                $report .= $_key . ": " . $_value . $sep;
            }
        }

        $report .= "Duration: " . $this->duration . " sec." . $sep;

        $report .= "---------------------" . $sep;
        $report .= "Intervals: " . $this->intervals_count . $sep;

        /**
         * @param Interval $interval
         * @param int $level
         * @return string
         */
        $intervals_string = function (Interval $interval, int $level) use (&$intervals_string) {

            $tab = str_repeat("  ", $level);

            $report_item_string = $tab
                . "- "
                . $interval->description
                . " | "
                . $interval->duration
                . " sec."
                . PHP_EOL;

            if ($interval->extras()->get()) {

                foreach ($interval->extras()->get() as $_key => $_value) {
                    $report_item_string .= $tab;
                    $report_item_string .= "  " . $_key . ": " . $_value . PHP_EOL;
                }

            }

            if (!empty($interval->children)) {
                foreach ($interval->children as $_child) {
                    $report_item_string .= $intervals_string($_child, $level + 1);
                }
            }

            return $report_item_string;

        };

        if (!empty($this->intervals)) {
            foreach ($this->intervals as $_interval) {
                $report_items .= $intervals_string($_interval, 1);
            }
        }

        $report .= $report_items . $sep;

        return $report;
    }

    /**
     * Report as JSON-string.
     *
     * @return string
     */
    public function toJSON(): string
    {
        $report = new \stdClass();

        $report->time = $this->time;
        $report->http_method = $this->http_method;
        $report->uri = $this->uri;
        $report->params = (object)$this->request_params;
        $report->description = $this->description;
        $report->time = $this->time;
        $report->ip_address = $this->ip_address;

        $report->extras = [];

        if ($this->extras()->get()) {
            foreach ($this->extras()->get() as $_key => $_value) {
                $report->extras[$_key] = $_value;
            }
        }

        $report->duration = \round($this->duration, Timer::DURATION_PRECISION);
        $report->intervals_count = $this->intervals_count;
        $report->intervals = [];

        /**
         * @param Interval $interval
         * @param int $level
         * @return string
         */
        $intervals_object = function (Interval $interval, int $level) use (&$intervals_object) {

            $obj = new \stdClass();

            $obj->description = $interval->description;
            $obj->duration = \round($interval->duration, Timer::DURATION_PRECISION);
            $obj->start = \round($interval->start, Timer::DURATION_PRECISION);
            $obj->end = \round($interval->end, Timer::DURATION_PRECISION);

            if ($interval->extras()->get()) {
                $obj->extras = [];
                foreach ($interval->extras()->get() as $_key => $_value) {
                    $obj->extras[$_key] = $_value;
                }
            } else {
                $obj->extras = new \stdClass();
            }

            $obj->children = [];

            if (!empty($interval->children)) {
                foreach ($interval->children as $_child) {
                    $obj->children[] = $intervals_object($_child, $level + 1);
                }
            }

            return $obj;

        };

        if (!empty($this->intervals)) {
            foreach ($this->intervals as $_interval) {
                $report->intervals[] = $intervals_object($_interval, 1);
            }
        }

        return $this->JSONEncode($report);
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

    /**
     * JSON representation of a value with local needed options.
     *
     * @param mixed $value
     * @return string
     */
    protected function JSONEncode($value): string
    {
        $json = \json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return ($json !== false) ? $json : \json_encode("");
    }
}