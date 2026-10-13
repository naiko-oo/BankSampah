<?php

return static function (TestCase $test): void {
    $test->test('accepts a new member with required fields and a strong password', static function () use ($test): void {
        $test->assertSame([], member_validation_errors('Budi Santoso', 'budi', 'password123', true));
    });

    $test->test('requires a name and a username within schema limits', static function () use ($test): void {
        $errors = member_validation_errors('', str_repeat('u', 51), 'password123', true);
        $test->assertSame(2, count($errors));
    });

    $test->test('requires a password of at least eight characters for a new member', static function () use ($test): void {
        $test->assertSame(1, count(member_validation_errors('Budi', 'budi', '1234567', true)));
    });

    $test->test('allows an unchanged password when editing a member', static function () use ($test): void {
        $test->assertSame([], member_validation_errors('Budi', 'budi', '', false));
    });

    $test->test('rejects a short replacement password when editing a member', static function () use ($test): void {
        $test->assertSame(1, count(member_validation_errors('Budi', 'budi', 'short', false)));
    });
};
