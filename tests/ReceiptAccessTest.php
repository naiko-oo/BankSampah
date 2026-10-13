<?php

return static function (TestCase $test): void {
    $test->test('allows an admin to view any transaction receipt', static function () use ($test): void {
        $test->assertTrue(transaction_visible_to_user('admin', 1, 42));
    });

    $test->test('allows a customer to view their own receipt', static function () use ($test): void {
        $test->assertTrue(transaction_visible_to_user('nasabah', 42, 42));
    });

    $test->test('denies a customer access to another customers receipt', static function () use ($test): void {
        $test->assertFalse(transaction_visible_to_user('nasabah', 42, 43));
    });

    $test->test('denies unknown roles access to transaction receipts', static function () use ($test): void {
        $test->assertFalse(transaction_visible_to_user('operator', 1, 42));
        $test->assertFalse(transaction_visible_to_user('', 1, 1));
    });
};
