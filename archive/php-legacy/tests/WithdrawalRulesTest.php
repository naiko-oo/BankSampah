<?php

return static function (TestCase $test): void {
    $test->test('allows a positive withdrawal below the available balance', static function () use ($test): void {
        $test->assertSame(null, withdrawal_validation_error(25, 100));
    });

    $test->test('allows withdrawing the exact available balance', static function () use ($test): void {
        $test->assertSame(null, withdrawal_validation_error(100, 100));
    });

    $test->test('rejects a withdrawal greater than the available balance', static function () use ($test): void {
        $test->assertSame(
            'Saldo nasabah tidak mencukupi untuk penarikan tersebut.',
            withdrawal_validation_error(100.01, 100)
        );
    });

    $test->test('rejects zero, negative, non-finite, and out-of-range withdrawal amounts', static function () use ($test): void {
        foreach ([0, -1, INF, 10000000000] as $amount) {
            $test->assertSame(
                'Nominal penarikan harus lebih besar dari nol dan tidak melebihi batas sistem.',
                withdrawal_validation_error($amount, 10000000000)
            );
        }
    });

    $test->test('rejects an invalid or negative balance', static function () use ($test): void {
        foreach ([-1, 'invalid', INF] as $balance) {
            $test->assertThrows(InvalidArgumentException::class, static function () use ($balance): void {
                withdrawal_validation_error(1, $balance);
            });
        }
    });
};
