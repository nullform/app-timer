# AppTimer

Timing for PHP apps with nested intervals.

## Installation

    composer require nullform/app-timer

## Usage

### Basic usage

```php
use Nullform\AppTimer;

$timer = new AppTimer\Timer("New timer");

$timer->start("First interval");
// ...
$timer->stop();

$timer->start("Second interval");
// ...
$timer->stop();

$report = $timer->report();
```

### Nested intervals

You can create new (nested) intervals within others.

```php
$timer->start("First interval"); // Parent interval

$timer->start("First nested interval");
// ...
$timet->stop();

$timer->start("Second nested interval");
// ...
$timet->stop();

$timer->stop(); // Stop parent interval

$report = $timer->report();
```

### Additional information for timer/interval

You can add additional information for the timer/interval to be reflected in the report.

```php
$timer = new AppTimer\Timer("New timer", ['Size' => "XXL"]);
$timer->start("New interval", ['Color' => "Red"]);
```

### Report

The report can be generated as a human-readable string or in JSON format. You can save the report to a file.

```php
// Create new timer
$timer = new AppTimer\Timer("New timer");

// Report file options
$timer->report_filename = "AppTimerReport.log";
$timer->report_dir = dirname(__FILE__);
$timer->report_file_append = true;

$timer->start("New interval");
// ...
$timer->stop();

// Create report
$report = $timer->report();

$report_json = $report->toJSON();
$report_string = $report->toString();
```

