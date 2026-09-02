<?php

/**
 * Dependency-free test runner: discovers tests/Case/*Test.php, runs every public method beginning with
 * "test", and reports one line per file.
 *
 * Assertions come from Kumwe\Transaction\Tests\TestCase. No framework, so the suite runs on any supported
 * PHP with no composer install. Discovery fails closed: deleting the suite, or leaving a discovered case
 * with no test methods, is a build failure rather than an empty success.
 *
 * @since 0.1.0
 */

declare(strict_types=1);

use Kumwe\Transaction\Tests\TestCase;

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Kumwe\\Transaction\\Tests\\' => __DIR__ . '/',
        'Kumwe\\Transaction\\' => dirname(__DIR__) . '/src/',
    ];
    foreach ($prefixes as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $path = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($path)) {
                require $path;
            }

            return;
        }
    }
});

$files = glob(__DIR__ . '/Case/*Test.php');
$files = $files === false ? [] : $files;
sort($files);

if ($files === []) {
    fwrite(STDERR, "Transaction suite failed: no test case files were discovered.\n");
    exit(1);
}

$totalTests = 0;
$totalAssertions = 0;
$failures = [];

foreach ($files as $file) {
    $class = 'Kumwe\\Transaction\\Tests\\Case\\' . basename($file, '.php');
    if (!class_exists($class)) {
        $failures[] = "{$file} declares no {$class}.";
        continue;
    }
    $case = new $class();
    if (!$case instanceof TestCase) {
        $failures[] = "{$class} does not extend the suite's TestCase.";
        continue;
    }
    $ran = 0;
    foreach (get_class_methods($case) as $method) {
        if (!str_starts_with($method, 'test')) {
            continue;
        }
        $totalTests++;
        $ran++;
        try {
            (new ReflectionMethod($case, $method))->invoke($case);
        } catch (Throwable $error) {
            $failures[] = sprintf(
                '%s::%s - %s (%s:%d)',
                $class,
                $method,
                $error->getMessage(),
                basename($error->getFile()),
                $error->getLine(),
            );
        }
    }
    if ($ran === 0) {
        $failures[] = "{$class} declares no public test methods.";
    }
    $totalAssertions += $case->assertionCount();
    echo sprintf("%-44s %3d tests\n", basename($file), $ran);
}

if ($totalTests === 0) {
    $failures[] = 'No tests ran.';
}

if ($failures !== []) {
    fwrite(STDERR, "\nFailures:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

echo "\nTransaction suite passed: {$totalTests} tests, {$totalAssertions} assertions.\n";
