<?php
/**
 * Minimal, dependency-free test runner for NevererWeb PHP code.
 * No PHPUnit (kept deliberately lightweight per project convention) - run via:
 *   php tests/php/run.php
 */

class TestFailure extends \Exception {}

class TestRunner {
    private static array $results = [];
    private static string $currentSuite = '';

    public static function suite(string $name, callable $fn): void {
        fwrite(STDOUT, "\n{$name}\n");
        self::$currentSuite = $name;
        $fn();
    }

    public static function test(string $name, callable $fn): void {
        $label = self::$currentSuite !== '' ? self::$currentSuite . ' > ' . $name : $name;
        try {
            $fn();
            self::$results[] = ['name' => $label, 'pass' => true];
            fwrite(STDOUT, "  PASS  {$name}\n");
        } catch (\Throwable $e) {
            self::$results[] = ['name' => $label, 'pass' => false, 'error' => $e];
            fwrite(STDOUT, "  FAIL  {$name}\n");
            fwrite(STDOUT, "        " . get_class($e) . ": " . $e->getMessage() . "\n");
            fwrite(STDOUT, "        at " . $e->getFile() . ":" . $e->getLine() . "\n");
        }
    }

    public static function summary(): int {
        $total = count(self::$results);
        $failed = count(array_filter(self::$results, fn($r) => !$r['pass']));
        $passed = $total - $failed;
        fwrite(STDOUT, "\n" . str_repeat('-', 60) . "\n");
        fwrite(STDOUT, "Tests: {$total}  Passed: {$passed}  Failed: {$failed}\n");
        return $failed > 0 ? 1 : 0;
    }
}

function assertTrue($actual, string $message = ''): void {
    if ($actual !== true) {
        throw new TestFailure($message ?: "Expected true, got " . var_export($actual, true));
    }
}
function assertFalse($actual, string $message = ''): void {
    if ($actual !== false) {
        throw new TestFailure($message ?: "Expected false, got " . var_export($actual, true));
    }
}
function assertEqual($expected, $actual, string $message = ''): void {
    if ($expected !== $actual) {
        throw new TestFailure(($message ? $message . "\n" : '') . "Expected: " . var_export($expected, true) . "\nActual:   " . var_export($actual, true));
    }
}
function assertNull($actual, string $message = ''): void {
    if ($actual !== null) {
        throw new TestFailure($message ?: "Expected null, got " . var_export($actual, true));
    }
}
function assertNotNull($actual, string $message = ''): void {
    if ($actual === null) {
        throw new TestFailure($message ?: "Expected non-null value");
    }
}
function assertStringContains(string $needle, string $haystack, string $message = ''): void {
    if (strpos($haystack, $needle) === false) {
        throw new TestFailure(($message ? $message . "\n" : '') . "Expected haystack to contain:\n  {$needle}\nHaystack was:\n  {$haystack}");
    }
}
function assertStringNotContains(string $needle, string $haystack, string $message = ''): void {
    if (strpos($haystack, $needle) !== false) {
        throw new TestFailure(($message ? $message . "\n" : '') . "Expected haystack NOT to contain:\n  {$needle}");
    }
}
function assertThrows(callable $fn, ?string $expectedMessageSubstring = null, string $message = ''): void {
    try {
        $fn();
    } catch (\Throwable $e) {
        if ($expectedMessageSubstring !== null && strpos($e->getMessage(), $expectedMessageSubstring) === false) {
            throw new TestFailure(($message ? $message . "\n" : '') . "Expected exception message to contain '{$expectedMessageSubstring}', got: " . $e->getMessage());
        }
        return; // pass
    }
    throw new TestFailure($message ?: "Expected an exception to be thrown, but none was.");
}
