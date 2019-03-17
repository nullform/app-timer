<?php

namespace Nullform\AppTimer;

/**
 * Class for measure execution time of code sections.
 *
 * @package Nullform\AppTimer
 */
class Timer
{
    /**
     * Number of decimal digits in duration.
     */
    const DURATION_PRECISION = 4;

    /**
     * Directory for the report file (if necessary).
     *
     * @var string
     */
    public $report_dir = "";

    /**
     * Name of the report file (if necessary).
     *
     * @var string
     */
    public $report_filename = "";

    /**
     * Append (not overwrite) the report file.
     *
     * @var bool
     */
    public $report_file_append = false;

    /**
     * All intervals.
     *
     * @var Interval[]
     */
    protected $intervals = [];

    /**
     * Current interval.
     *
     * @var Interval|null
     */
    protected $current_interval;

    /**
     * Timer report.
     *
     * @var Report
     */
    protected $report;


    /**
     * @param string $description
     * @param array $extras Additional information (associative array width [string => string] pairs).
     */
    public function __construct(string $description = "", array $extras = [])
    {
        $this->report = new Report();

        $this->report->ip_address = (string)\getenv("REMOTE_ADDR");

        $this->report->sapi = \php_sapi_name();

        $this->report->time = \date("Y-m-d H:i:s");

        if (!empty($_SERVER['HTTP_HOST'])) {
            $this->report->uri = (string)$_SERVER['REQUEST_SCHEME'] . "://"
                . (string)$_SERVER['HTTP_HOST']
                . (string)$_SERVER['REQUEST_URI'];
        }

        $this->report->http_method = (string)$_SERVER['REQUEST_METHOD'];

        $this->report->description = (string)$description;

        $this->report->request_params = $_REQUEST;

        if (!empty($extras)) {
            foreach ($extras as $_key => $_value) {
                $this->report->extras()->add((string)$_key, (string)$_value);
            }
        }
    }

    /**
     * Create and start new interval.
     *
     * Example:
     *
     * ```
     * $timer = new AppTimer\Timer("Description");
     * $interval = $timer->start("First interval");
     * ```
     *
     * @param string $description Interval description.
     * @param array $extras Additional information (associative array width [string => string] pairs).
     * @return Interval Started interval.
     * @throws Exceptions\TimerException
     */
    public function start(string $description, array $extras = []): Interval
    {

        try {

            // Create new interval
            $interval = new Interval($description);

        } catch (Exceptions\IntervalException $exception) {

            throw new Exceptions\TimerException($exception->getMessage());

        }

        if (!empty($extras)) {
            foreach ($extras as $_key => $_value) {
                $interval->extras()->add((string)$_key, (string)$_value);
            }
        }

        if (is_null($this->current_interval)) { // Add to main stack

            \array_push($this->intervals, $interval);

        } else { // Add nested interval

            $interval->parent = $this->current_interval->id;

            \array_push($this->current_interval->children, $interval);

        }

        $this->current_interval = $interval;

        $interval->start = \microtime(true);

        return $interval;
    }

    /**
     * Stop current interval.
     *
     * Example:
     *
     * ```
     * $timer = new AppTimer\Timer("Description");
     * $timer->start("First interval");
     * // Some code
     * $timer->stop();
     * ```
     *
     * @return Interval Stopped interval.
     * @throws Exceptions\TimerException
     */
    public function stop(): Interval
    {
        $end = \microtime(true);

        if (!($this->current_interval instanceof Interval)) {
            throw new Exceptions\TimerException("No interval found for stopping");
        }

        $this->current_interval->end = $end;
        $this->current_interval->duration = (float)\bcsub(
            $this->current_interval->end,
            $this->current_interval->start,
            self::DURATION_PRECISION
        );

        $parent = $this->current_interval->parent;
        $interval = $this->current_interval;

        unset($this->current_interval);

        if (!empty($parent)) {
            $this->current_interval = $this->getIntervalById($parent, $this->intervals);
        }

        return $interval;
    }

    /**
     * Stop all intervals.
     */
    public function stopAll(): void
    {
        if ($this->current_interval instanceof Interval) {

            $this->stop(); // Stop current interval

            if ($this->current_interval instanceof Interval) { // If the stopped interval had a parent interval
                $this->stopAll(); // Stop parent intervals recursive
            }

        }
    }

    /**
     * Stop all intervals and create a report.
     *
     * If you want to save the report to a file, specify the file name and folder in the appropriate
     * properties (report_dir and report_filename).
     *
     * @return Report
     * @uses Timer::stopAll()
     * @throws Exceptions\TimerException
     */
    public function report(): Report
    {
        $report_footer = "======================================================\n\n";

        $this->stopAll();

        $this->report->setIntervals($this->intervals);

        $this->report_dir = (string)$this->report_dir;
        $this->report_filename = (string)$this->report_filename;

        if (!empty($this->report_dir)) {
            if (!\is_dir($this->report_dir) || !\is_writable($this->report_dir)) {
                throw new Exceptions\TimerException("Report dir not found or not writable");
            } else {
                $this->report_dir = \preg_replace("/(\/|\\\)$/", "", $this->report_dir);
                $this->report_dir .= DIRECTORY_SEPARATOR;
            }
        }

        if (!empty($this->report_dir) && !empty($this->report_filename)) {

            $this->report_filename = (string)$this->report_filename;

            $report_string = $this->report->toString();

            \file_put_contents(
                $this->report_dir . $this->report_filename,
                ($this->report_file_append) ? $report_string . $report_footer : $report_string,
                ($this->report_file_append) ? FILE_APPEND : 0
            );

        }

        return $this->report;
    }

    /**
     * Find interval by its unique id.
     *
     * @param string $id
     * @param Interval[] $intervals
     * @return Interval|null
     */
    protected function getIntervalById(string $id, array $intervals): ?Interval
    {
        $interval = null;

        foreach ($intervals as $_interval) {
            if ($_interval->id == $id) {
                $interval = $_interval;
            } else {
                if (!empty($_interval->children)) {
                    $interval = $this->getIntervalById($id, $_interval->children);
                }
            }
        }

        return $interval;
    }
}