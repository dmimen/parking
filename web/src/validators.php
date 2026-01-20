<?php

declare(strict_types=1);

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    return $digits ?? '';
}

function normalize_car_number(string $number): string
{
    $clean = preg_replace('/\s+/', '', $number);
    $clean = strtoupper($clean ?? '');
    return $clean;
}
