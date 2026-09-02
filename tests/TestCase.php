<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Tests;

/**
 * Minimal assertion base for the dependency-free suite; a failed assertion throws and the runner reports it.
 *
 * @since  0.1.0
 */
abstract class TestCase
{
    /**
     * Assertions this case has made so far, for the runner's summary line.
     *
     * @var    int
     * @since  0.1.0
     */
    private int $assertions = 0;

    /**
     * Number of assertions made by this case.
     *
     * @return  int  Assertion count.
     *
     * @since   0.1.0
     */
    final public function assertionCount(): int
    {
        return $this->assertions;
    }

    /**
     * Require a condition to hold.
     *
     * @param   bool    $condition  Outcome under test.
     * @param   string  $message    Why the outcome matters, reported on failure.
     *
     * @return  void
     *
     * @throws  \RuntimeException  When the condition is false.
     *
     * @since   0.1.0
     */
    final protected function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    /**
     * Require a condition not to hold.
     *
     * @param   bool    $condition  Outcome under test.
     * @param   string  $message    Why the outcome matters, reported on failure.
     *
     * @return  void
     *
     * @throws  \RuntimeException  When the condition is true.
     *
     * @since   0.1.0
     */
    final protected function assertFalse(bool $condition, string $message): void
    {
        $this->assertions++;
        if ($condition) {
            throw new \RuntimeException($message);
        }
    }

    /**
     * Require strict identity between an expectation and an outcome.
     *
     * @param   mixed   $expected  Expected value.
     * @param   mixed   $actual    Observed value.
     * @param   string  $message   Why the outcome matters, reported on failure.
     *
     * @return  void
     *
     * @throws  \RuntimeException  When the values are not identical.
     *
     * @since   0.1.0
     */
    final protected function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new \RuntimeException(
                $message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.',
            );
        }
    }

    /**
     * Require a substring to be present.
     *
     * @param   string  $needle    Substring that must appear.
     * @param   string  $haystack  Text under test.
     * @param   string  $message   Why the substring matters, reported on failure.
     *
     * @return  void
     *
     * @throws  \RuntimeException  When the substring is absent.
     *
     * @since   0.1.0
     */
    final protected function assertStringContains(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (!str_contains($haystack, $needle)) {
            throw new \RuntimeException($message . ' Missing: ' . $needle);
        }
    }

    /**
     * Require a substring to be absent.
     *
     * @param   string  $needle    Substring that must not appear.
     * @param   string  $haystack  Text under test.
     * @param   string  $message   Why the substring is forbidden, reported on failure.
     *
     * @return  void
     *
     * @throws  \RuntimeException  When the substring is present.
     *
     * @since   0.1.0
     */
    final protected function assertStringExcludes(string $needle, string $haystack, string $message): void
    {
        $this->assertions++;
        if (str_contains($haystack, $needle)) {
            throw new \RuntimeException($message . ' Forbidden substring present: ' . $needle);
        }
    }

    /**
     * Require an operation to throw a given exception type, and hand the instance back for inspection.
     *
     * @param   callable(): mixed          $operation       Work expected to throw.
     * @param   class-string<\Throwable>   $exceptionClass  Type the thrown instance must be.
     * @param   string                     $message         Why the refusal matters, reported on failure.
     *
     * @return  \Throwable  The instance that was thrown.
     *
     * @throws  \RuntimeException  When nothing or something of another type is thrown.
     *
     * @since   0.1.0
     */
    final protected function assertThrows(callable $operation, string $exceptionClass, string $message): \Throwable
    {
        $this->assertions++;
        try {
            $operation();
        } catch (\Throwable $error) {
            if (!$error instanceof $exceptionClass) {
                throw new \RuntimeException(
                    $message . ' Threw ' . $error::class . ' instead of ' . $exceptionClass
                        . ': ' . $error->getMessage(),
                );
            }

            return $error;
        }

        throw new \RuntimeException($message . ' Nothing was thrown; expected ' . $exceptionClass . '.');
    }

    /**
     * The repository root.
     *
     * @return  string  Absolute path without a trailing slash.
     *
     * @since   0.1.0
     */
    final protected function root(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Read one repository file completely.
     *
     * @param   string  $relative  Repository-relative path.
     *
     * @return  string  File bytes.
     *
     * @throws  \RuntimeException  When the file is missing or unreadable.
     *
     * @since   0.1.0
     */
    final protected function read(string $relative): string
    {
        $path = $this->root() . '/' . $relative;
        $bytes = is_file($path) ? file_get_contents($path) : false;
        if (!is_string($bytes)) {
            throw new \RuntimeException('Cannot read ' . $relative . '.');
        }

        return $bytes;
    }

    /**
     * Decode one repository JSON object file.
     *
     * @param   string  $relative  Repository-relative path.
     *
     * @return  array<string, mixed>  The decoded object.
     *
     * @throws  \RuntimeException  When the file is not a JSON object.
     *
     * @since   0.1.0
     */
    final protected function json(string $relative): array
    {
        $decoded = json_decode($this->read($relative), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new \RuntimeException($relative . ' is not a JSON object.');
        }
        $object = [];
        foreach ($decoded as $key => $value) {
            $object[(string) $key] = $value;
        }

        return $object;
    }

    /**
     * Run one of the repository's PHP entry points and capture its exit status and combined output.
     *
     * @param   list<string>  $arguments  Repository-relative script path followed by its arguments.
     *
     * @return  array{status: int, output: string}  Exit status and the combined standard streams.
     *
     * @since   0.1.0
     */
    final protected function runScript(array $arguments): array
    {
        $command = escapeshellarg(PHP_BINARY);
        foreach ($arguments as $index => $argument) {
            $command .= ' ' . escapeshellarg($index === 0 ? $this->root() . '/' . $argument : $argument);
        }
        $output = [];
        $status = 1;
        exec($command . ' 2>&1', $output, $status);

        return ['status' => $status, 'output' => implode("\n", $output)];
    }
}
