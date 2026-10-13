<?php

return static function (TestCase $test): void {
    $test->test('accepts a named category with a positive tariff', static function () use ($test): void {
        $test->assertSame([], category_validation_errors('Botol Plastik', '3500.00'));
    });

    $test->test('rejects an empty category name', static function () use ($test): void {
        $errors = category_validation_errors('', 3500);
        $test->assertSame(1, count($errors));
    });

    $test->test('rejects a category name longer than the schema limit', static function () use ($test): void {
        $errors = category_validation_errors(str_repeat('a', 101), 3500);
        $test->assertSame(1, count($errors));
    });

    $test->test('rejects zero, negative, and non-numeric tariffs', static function () use ($test): void {
        foreach ([0, -1, 'not-a-price'] as $price) {
            $test->assertSame(1, count(category_validation_errors('Kertas', $price)));
        }
    });

    $test->test('rejects tariffs outside the supported database range', static function () use ($test): void {
        $test->assertSame(1, count(category_validation_errors('Kertas', '100000000')));
        $test->assertSame(1, count(category_validation_errors('Kertas', 'NAN')));
    });
};
