<?php

/**
 * Syntax-check every PHP file and hold every tracked text file to the 120-column limit.
 *
 * Dependency-free so the lane runs before any composer install. The syntax pass covers the source, the
 * suite, the tooling, the examples and the shipped toolchain resource; the column pass covers every file
 * git tracks, falling back to a tree walk that skips generated directories when git is unavailable, so a
 * document, a manifest or a workflow cannot exceed the limit the coding standard sets for code.
 *
 * @since 0.1.0
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$syntaxChecked = 0;

foreach (['src', 'tests', 'tools', 'examples', 'resources/toolchain'] as $directory) {
    $path = $root . '/' . $directory;
    if (!is_dir($path)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }
        $syntaxChecked++;
        $output = [];
        $status = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $status);
        if ($status !== 0) {
            $failures[] = implode("\n", $output);
        }
    }
}

$tracked = [];
$listed = [];
$listStatus = 0;
exec(
    'git -C ' . escapeshellarg($root) . ' ls-files --cached --others --exclude-standard 2>/dev/null',
    $listed,
    $listStatus,
);
if ($listStatus === 0 && $listed !== []) {
    foreach ($listed as $relative) {
        if (is_file($root . '/' . $relative)) {
            $tracked[] = $relative;
        }
    }
} else {
    $skipped = ['.git', '.phpstan.cache', 'dist', 'vendor'];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static fn (mixed $entry): bool => $entry instanceof SplFileInfo
                && !in_array($entry->getFilename(), $skipped, true),
        ),
    );
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $tracked[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }
}
sort($tracked);

$columnChecked = 0;
foreach ($tracked as $relative) {
    $contents = file_get_contents($root . '/' . $relative);
    if (!is_string($contents) || str_contains($contents, "\0")) {
        continue;
    }
    $columnChecked++;
    foreach (explode("\n", $contents) as $offset => $line) {
        $length = preg_match_all('/./su', rtrim($line, "\r"));
        if ($length !== false && $length > 120) {
            $failures[] = sprintf('%s:%d is %d columns wide; the limit is 120.', $relative, $offset + 1, $length);
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Lint failed:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

echo "Lint passed: {$syntaxChecked} PHP files syntax-checked, {$columnChecked} tracked files within 120 columns.\n";
