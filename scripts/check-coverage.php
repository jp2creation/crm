<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/check-coverage.php <clover.xml> [minimum-percent]\n");

    exit(2);
}

$cloverPath = $argv[1];
$minimum = isset($argv[2]) ? (float) $argv[2] : 80.0;

if (! is_file($cloverPath)) {
    fwrite(STDERR, "Coverage report not found: {$cloverPath}\n");

    exit(2);
}

$report = simplexml_load_file($cloverPath);

if ($report === false) {
    fwrite(STDERR, "Unable to read coverage report: {$cloverPath}\n");

    exit(2);
}

$covered = 0;
$statements = 0;

foreach ($report->xpath('//file/metrics') ?: [] as $metrics) {
    $covered += (int) $metrics['coveredstatements'];
    $statements += (int) $metrics['statements'];
}

if ($statements === 0) {
    fwrite(STDERR, "Coverage report contains no executable statements.\n");

    exit(2);
}

$coverage = ($covered / $statements) * 100;
$formattedCoverage = number_format($coverage, 2);
$formattedMinimum = number_format($minimum, 2);

echo "Line coverage: {$formattedCoverage}% ({$covered}/{$statements}), minimum {$formattedMinimum}%\n";

if ($coverage + 0.00001 < $minimum) {
    fwrite(STDERR, "Coverage threshold failed.\n");

    exit(1);
}

exit(0);
