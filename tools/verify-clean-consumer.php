<?php

/**
 * Build the consumer archive from this checkout and prove it installs and runs with no development state.
 *
 * This is the release gate a package checkout cannot fake: the archive Composer would publish is built,
 * extracted, held to the reviewed file set, validated, installed with `--no-dev --classmap-authoritative`
 * into its own directory, and then exercised through the shipped autoload smoke and the shipped example —
 * so a consumer artifact that needs a test fixture, a path repository, a development package or an
 * undeclared dependency fails here rather than at adoption.
 *
 * Set KUMWE_CLEAN_CONSUMER_COMPOSER_ARGS to pass extra options to the archive's install; a local runtime
 * outside the declared PHP range passes `--ignore-platform-req=php` that way. CI never sets it.
 *
 * @since 0.1.0
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$workspace = rtrim(sys_get_temp_dir(), '/\\') . '/kumwe-transaction-consumer-' . bin2hex(random_bytes(6));
$extraEnvironment = getenv('KUMWE_CLEAN_CONSUMER_COMPOSER_ARGS');
$extra = [];
if (is_string($extraEnvironment) && trim($extraEnvironment) !== '') {
    $split = preg_split('/\s+/', trim($extraEnvironment));
    $extra = $split === false ? [] : $split;
}

/**
 * Run one command, streaming its output, and stop the gate on the first failure.
 *
 * @param   list<string>  $arguments  Program followed by its arguments.
 * @param   string        $workspace  Temporary directory to remove on failure.
 *
 * @return  void
 *
 * @since   0.1.0
 */
function consumerRun(array $arguments, string $workspace): void
{
    echo '> ' . implode(' ', $arguments) . "\n";
    $status = 1;
    passthru(implode(' ', array_map(escapeshellarg(...), $arguments)) . ' 2>&1', $status);
    if ($status !== 0) {
        consumerFail('the command exited with status ' . $status . '.', $workspace);
    }
}

/**
 * Report one failure, remove the workspace and exit.
 *
 * @param   string  $message    What failed.
 * @param   string  $workspace  Temporary directory to remove.
 *
 * @return  never
 *
 * @since   0.1.0
 */
function consumerFail(string $message, string $workspace): never
{
    fwrite(STDERR, 'Clean consumer verification failed: ' . $message . "\n");
    consumerRemove($workspace);
    exit(1);
}

/**
 * Remove a directory tree created by this gate.
 *
 * @param   string  $path  Directory to remove; a missing path is ignored.
 *
 * @return  void
 *
 * @since   0.1.0
 */
function consumerRemove(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $entry) {
        if (!$entry instanceof SplFileInfo) {
            continue;
        }
        if ($entry->isDir() && !$entry->isLink()) {
            rmdir($entry->getPathname());
        } else {
            unlink($entry->getPathname());
        }
    }
    rmdir($path);
}

if (!mkdir($workspace, 0777, true) || !is_dir($workspace)) {
    fwrite(STDERR, "Clean consumer verification failed: cannot create a temporary workspace.\n");
    exit(1);
}

$distDirectory = $workspace . '/dist';
consumerRun(
    [
        'composer',
        '--working-dir=' . $root,
        'archive',
        '--format=zip',
        '--dir=' . $distDirectory,
        '--file=kumwe-transaction',
    ],
    $workspace,
);
$archive = $distDirectory . '/kumwe-transaction.zip';
if (!is_file($archive)) {
    consumerFail('Composer did not create the expected archive.', $workspace);
}

$zip = new ZipArchive();
if ($zip->open($archive) !== true) {
    consumerFail('the archive cannot be opened.', $workspace);
}
$packageRoot = $workspace . '/package';
if (!mkdir($packageRoot) || !$zip->extractTo($packageRoot)) {
    consumerFail('the archive cannot be extracted.', $workspace);
}
$zip->close();

$package = $packageRoot;
if (!is_file($package . '/composer.json')) {
    $directories = glob($packageRoot . '/*', GLOB_ONLYDIR);
    $directories = $directories === false ? [] : $directories;
    if (count($directories) !== 1 || !is_file($directories[0] . '/composer.json')) {
        consumerFail('the archive does not contain one package root.', $workspace);
    }
    $package = $directories[0];
}

consumerRun([PHP_BINARY, $root . '/tools/verify-archive.php', $package], $workspace);
consumerRun(['composer', '--working-dir=' . $package, 'validate', '--strict'], $workspace);
consumerRun(
    [
        'composer',
        '--working-dir=' . $package,
        'install',
        '--no-interaction',
        '--no-progress',
        '--no-dev',
        '--classmap-authoritative',
        ...$extra,
    ],
    $workspace,
);
foreach (['phpstan', 'squizlabs'] as $developmentVendor) {
    if (is_dir($package . '/vendor/' . $developmentVendor)) {
        consumerFail('the no-dev install still contains vendor/' . $developmentVendor . '.', $workspace);
    }
}
consumerRun(['composer', '--working-dir=' . $package, 'autoload:smoke'], $workspace);
consumerRun(['composer', '--working-dir=' . $package, 'examples'], $workspace);

$classmap = require $package . '/vendor/composer/autoload_classmap.php';
$manifestBytes = file_get_contents($package . '/resources/public-api/v1.json');
$manifest = is_string($manifestBytes) ? json_decode($manifestBytes, true) : null;
$symbols = is_array($manifest) && is_array($manifest['symbols'] ?? null) ? $manifest['symbols'] : [];
if (!is_array($classmap) || $symbols === []) {
    consumerFail('the installed classmap or the shipped manifest cannot be read.', $workspace);
}
foreach (array_keys($symbols) as $symbol) {
    if (!array_key_exists($symbol, $classmap)) {
        consumerFail('the authoritative classmap does not list ' . $symbol . '.', $workspace);
    }
}

$fileCount = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($package, FilesystemIterator::SKIP_DOTS),
);
foreach ($iterator as $entry) {
    if ($entry instanceof SplFileInfo && $entry->isFile() && !str_contains($entry->getPathname(), '/vendor/')) {
        $fileCount++;
    }
}
consumerRemove($workspace);

echo sprintf(
    "Clean consumer verified: a %d-file archive installed without development dependencies, "
        . "its %d public symbols in the authoritative classmap, smoke and example green.\n",
    $fileCount,
    count($symbols),
);
