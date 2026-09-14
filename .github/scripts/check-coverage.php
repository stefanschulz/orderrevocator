<?php

declare(strict_types=1);

/**
 * Fails (non-zero exit) if the project's line coverage, as reported in a
 * PHPUnit Clover report, drops below a minimum threshold.
 *
 * Usage: php check-coverage.php <clover.xml> <minimum-percent>
 */
[, $cloverPath, $minimumPercent] = $argv + [null, null, null];

if ($cloverPath === null || $minimumPercent === null) {
    fwrite(STDERR, "Usage: php check-coverage.php <clover.xml> <minimum-percent>\n");
    exit(2);
}

$xml = simplexml_load_file($cloverPath);

if ($xml === false) {
    fwrite(STDERR, "Could not parse Clover report at '{$cloverPath}'.\n");
    exit(2);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, "Clover report contains no statements to measure.\n");
    exit(2);
}

$percent = $covered / $statements * 100;
$minimum = (float) $minimumPercent;

printf("Line coverage: %.2f%% (%d/%d statements), minimum required: %.2f%%\n", $percent, $covered, $statements, $minimum);

if ($percent < $minimum) {
    fwrite(STDERR, "Coverage dropped below the required minimum.\n");
    exit(1);
}
