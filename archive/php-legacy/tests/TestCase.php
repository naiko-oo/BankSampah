<?php

final class TestCase
{
    private int $passed = 0;
    private int $failed = 0;

    public function test(string $name, callable $test): void
    {
        try {
            $test();
            $this->passed++;
            echo "[PASS] {$name}" . PHP_EOL;
        } catch (Throwable $exception) {
            $this->failed++;
            echo "[FAIL] {$name}: {$exception->getMessage()}" . PHP_EOL;
        }
    }

    public function assertSame($expected, $actual, string $message = 'Values are not identical'): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '; expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    public function assertTrue(bool $actual, string $message = 'Expected true'): void
    {
        $this->assertSame(true, $actual, $message);
    }

    public function assertFalse(bool $actual, string $message = 'Expected false'): void
    {
        $this->assertSame(false, $actual, $message);
    }

    public function assertThrows(string $exceptionClass, callable $operation, string $message = 'Expected exception was not thrown'): void
    {
        try {
            $operation();
        } catch (Throwable $exception) {
            if ($exception instanceof $exceptionClass) {
                return;
            }

            throw new RuntimeException('Expected ' . $exceptionClass . ', got ' . get_class($exception));
        }

        throw new RuntimeException($message);
    }

    public function finish(): int
    {
        echo PHP_EOL . "Results: {$this->passed} passed, {$this->failed} failed" . PHP_EOL;

        return $this->failed === 0 ? 0 : 1;
    }
}
