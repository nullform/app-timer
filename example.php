<?php

include "src/Extras.php";
include "src/Interval.php";
include "src/Report.php";
include "src/Timer.php";
include "src/Exceptions/ReportException.php";

/**
 * @param int $iterations
 */
$func_cycle_1 = function (int $iterations) {

    $values = [];

    for ($i = 0; $i < $iterations; $i++) {
        $values[] = md5(rand(0, 1000000));
    }

};

/**
 * @param int $iterations
 */
$func_cycle_2 = function (int $iterations) {

    $values = [];

    for ($i = 0; $i < $iterations; $i++) {
        $values[] = md5(rand(0, 1000000));
    }

};


// Create new timer
$timer = new Nullform\AppTimer\Timer("New timer", ['Foo' => "Bar"]);

// Report file options
// $timer->report_filename = "AppTimerReport.log";
// $timer->report_dir = dirname(__FILE__);
// $timer->report_file_append = true;


// Parent interval for cycles
$cycles_interval = $timer->start("Cycles");

// Interval for $func_cycle_1()
$timer->start("Cycle 1", ['Iterations' => "1000"]);
$func_cycle_1(1000);
$timer->stop();

// Interval for $func_cycle_2()
$cycle_2_interval = $timer->start("Cycle 2");
// Alternative way to add additional information
$cycle_2_interval->extras()->add("Iterations", "10000");
$func_cycle_2(10000);
$stopped_interval = $timer->stop(['Iterations complete' => 10000]);
$stopped_interval->extras()->remove("Iterations");

$timer->stop();


$timer->start("Going to sleep");
sleep(1);
$timer->stop(['Seconds' => 1]);

// Get parent interval duration
$cycles_interval_duration = $cycles_interval->duration;

// Create report
$report = $timer->report();

// Report as JSON-string
$report_json = $report->toJSON();
// Report as string
$report_string = $report->toString();


if ($report->sapi != "cli") {
    echo "<pre>" . $report_string . "</pre>";
} else {
    echo $report_string;
}