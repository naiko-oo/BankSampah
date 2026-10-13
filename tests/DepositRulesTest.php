<?php

return static function (TestCase $test): void {
    $test->test('calculates a deposit subtotal from weight and category tariff', static function () use ($test): void {
        $test->assertSame(8750.0, calculate_deposit_subtotal(2.5, 3500));
    });

    $test->test('rounds each deposit subtotal to two decimal places', static function () use ($test): void {
        $test->assertSame(3.34, calculate_deposit_subtotal(1.11, 3.005));
    });

    $test->test('accepts the minimum supported weight', static function () use ($test): void {
        $test->assertSame(35.0, calculate_deposit_subtotal(0.01, 3500));
    });

    $test->test('rejects zero, negative, non-finite, and out-of-range weights', static function () use ($test): void {
        foreach ([0, -1, INF, 1000000] as $weight) {
            $test->assertThrows(InvalidArgumentException::class, static function () use ($weight): void {
                calculate_deposit_subtotal($weight, 3500);
            });
        }
    });

    $test->test('rejects invalid tariffs and subtotals that round to zero', static function () use ($test): void {
        foreach ([0, -1, 'invalid'] as $price) {
            $test->assertThrows(InvalidArgumentException::class, static function () use ($price): void {
                calculate_deposit_subtotal(1, $price);
            });
        }
        $test->assertThrows(InvalidArgumentException::class, static function (): void {
            calculate_deposit_subtotal(0.01, 0.01);
        });
    });

    $test->test('rejects a subtotal above the supported transaction amount', static function () use ($test): void {
        $test->assertThrows(InvalidArgumentException::class, static function (): void {
            calculate_deposit_subtotal(999999.99, 99999999.99);
        });
    });
};
