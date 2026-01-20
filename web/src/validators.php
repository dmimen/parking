<?php

declare(strict_types=1);

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 10) {
        return '7' . $digits;
    }
    if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) {
        return '7' . substr($digits, 1);
    }
    return $digits;
}

function normalize_car_number(string $number): string
{
    $clean = preg_replace('/\s+/', '', $number);
    $clean = strtoupper($clean ?? '');
    return $clean;
}
