<?php

function category_validation_errors($name, $price): array
{
    $errors = [];

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Nama kategori wajib diisi dan maksimal 100 karakter.';
    }

    $validatedPrice = filter_var($price, FILTER_VALIDATE_FLOAT);
    if ($validatedPrice === false || !is_finite((float)$validatedPrice) || $validatedPrice <= 0 || $validatedPrice > 99999999.99) {
        $errors[] = 'Tarif per kilogram harus lebih besar dari nol dan tidak melebihi batas sistem.';
    }

    return $errors;
}

function member_validation_errors($name, $username, $password, bool $isNew): array
{
    $errors = [];

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Nama wajib diisi dan maksimal 100 karakter.';
    }
    if ($username === '' || strlen($username) > 50) {
        $errors[] = 'Username wajib diisi dan maksimal 50 karakter.';
    }
    if (($isNew && strlen($password) < 8) || ($password !== '' && strlen($password) < 8)) {
        $errors[] = $isNew ? 'Kata sandi akun baru minimal 8 karakter.' : 'Kata sandi baru minimal 8 karakter.';
    }

    return $errors;
}

function calculate_deposit_subtotal($weight, $price): float
{
    if (!is_numeric($weight) || !is_finite((float)$weight) || (float)$weight < 0.01 || (float)$weight > 999999.99) {
        throw new InvalidArgumentException('Berat setoran harus lebih besar dari nol dan tidak melebihi batas sistem.');
    }
    if (!is_numeric($price) || !is_finite((float)$price) || (float)$price <= 0 || (float)$price > 99999999.99) {
        throw new InvalidArgumentException('Tarif kategori tidak valid.');
    }

    $subtotal = round((float)$weight * (float)$price, 2);
    if ($subtotal <= 0 || $subtotal > 9999999999.99) {
        throw new InvalidArgumentException('Nilai setoran harus lebih besar dari nol dan tidak melebihi batas sistem.');
    }

    return $subtotal;
}

function withdrawal_validation_error($amount, $balance): ?string
{
    if (!is_numeric($amount) || !is_finite((float)$amount) || (float)$amount <= 0 || (float)$amount > 9999999999.99) {
        return 'Nominal penarikan harus lebih besar dari nol dan tidak melebihi batas sistem.';
    }
    if (!is_numeric($balance) || !is_finite((float)$balance) || (float)$balance < 0) {
        throw new InvalidArgumentException('Saldo nasabah tidak valid.');
    }
    if ((float)$amount > (float)$balance) {
        return 'Saldo nasabah tidak mencukupi untuk penarikan tersebut.';
    }

    return null;
}

function transaction_visible_to_user(string $role, int $viewerId, int $ownerId): bool
{
    return $role === 'admin' || ($role === 'nasabah' && $viewerId === $ownerId);
}
