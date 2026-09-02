<?php

/**
 * Prove the installed Composer autoloader exposes the complete public API.
 *
 * The script is itself a shipped package resource, so the same proof runs in the source checkout, after the
 * development packages are removed, and again inside the extracted consumer archive. Its symbol list comes
 * only from the package-owned public API manifest: each symbol must load through Composer, and the file the
 * manifest names for it must be the file the loaded declaration came from.
 *
 * @since 0.1.0
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$autoload = $root . '/vendor/autoload.php';
$manifestPath = $root . '/resources/public-api/v1.json';

if (!is_file($autoload)) {
    fwrite(STDERR, "Composer autoload smoke failed: vendor/autoload.php is missing.\n");
    exit(1);
}

/** @var mixed $loader */
$loader = require $autoload;

/**
 * Locate the file a manifest symbol of a known kind is declared in, loading it through the Composer autoloader.
 *
 * @param   string  $name  Canonical symbol name.
 * @param   mixed   $kind  Manifest kind.
 *
 * @return  string|null  The declaring file, or null when the symbol cannot be loaded as that kind.
 *
 * @since   0.1.0
 */
function transactionSmokeDeclaringFile(string $name, mixed $kind): ?string
{
    if ($kind === 'class' && class_exists($name)) {
        $file = (new ReflectionClass($name))->getFileName();
    } elseif ($kind === 'interface' && interface_exists($name)) {
        $file = (new ReflectionClass($name))->getFileName();
    } elseif ($kind === 'trait' && trait_exists($name)) {
        $file = (new ReflectionClass($name))->getFileName();
    } elseif ($kind === 'enum' && enum_exists($name)) {
        $file = (new ReflectionEnum($name))->getFileName();
    } else {
        return null;
    }

    return is_string($file) ? $file : null;
}

$contents = file_get_contents($manifestPath);
if (!is_string($contents)) {
    fwrite(STDERR, "Composer autoload smoke failed: the public API manifest is unreadable.\n");
    exit(1);
}

try {
    $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $error) {
    fwrite(STDERR, 'Composer autoload smoke failed: ' . $error->getMessage() . "\n");
    exit(1);
}

$symbols = is_array($manifest) ? ($manifest['symbols'] ?? null) : null;
if (!is_array($manifest) || ($manifest['schema'] ?? null) !== 'kumwe-package-public-api/v1') {
    fwrite(STDERR, "Composer autoload smoke failed: the manifest is not a Version 2 public API manifest.\n");
    exit(1);
}
if (!is_array($symbols) || $symbols === []) {
    fwrite(STDERR, "Composer autoload smoke failed: the public API manifest exports no symbol.\n");
    exit(1);
}

$failures = [];
foreach ($symbols as $name => $shape) {
    if (!is_string($name) || !is_array($shape)) {
        $failures[] = 'a malformed manifest entry';
        continue;
    }
    $declaredIn = transactionSmokeDeclaringFile($name, $shape['kind'] ?? null);
    if ($declaredIn === null) {
        $failures[] = $name . ' cannot be loaded as the kind the manifest records';
        continue;
    }
    $file = $shape['file'] ?? null;
    $expected = is_string($file) ? realpath($root . '/' . $file) : false;
    $actual = realpath($declaredIn);
    if (!is_string($expected) || !is_string($actual) || $expected !== $actual) {
        $failures[] = $name . ' was not loaded from the file the manifest names';
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Composer autoload smoke failed:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

$mode = $loader instanceof Composer\Autoload\ClassLoader && $loader->isClassMapAuthoritative()
    ? 'classmap-authoritative'
    : 'development';
echo sprintf("Composer autoload smoke passed: %d public symbols loaded (%s autoloader).\n", count($symbols), $mode);
