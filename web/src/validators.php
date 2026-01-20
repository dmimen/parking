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

function normalize_plate(string $number): string
{
    $clean = preg_replace('/[\s-]+/', '', $number) ?? '';
    $map = [
        'а' => 'A',
        'в' => 'B',
        'е' => 'E',
        'к' => 'K',
        'м' => 'M',
        'н' => 'H',
        'о' => 'O',
        'р' => 'P',
        'с' => 'C',
        'т' => 'T',
        'у' => 'Y',
        'х' => 'X',
        'А' => 'A',
        'В' => 'B',
        'Е' => 'E',
        'К' => 'K',
        'М' => 'M',
        'Н' => 'H',
        'О' => 'O',
        'Р' => 'P',
        'С' => 'C',
        'Т' => 'T',
        'У' => 'Y',
        'Х' => 'X',
    ];
    $normalized = strtr($clean, $map);
    return strtoupper($normalized);
}

function normalize_plate_sql(string $field): string
{
    $expr = "REPLACE(REPLACE(UPPER({$field}), ' ', ''), '-', '')";
    $map = [
        'А' => 'A',
        'В' => 'B',
        'Е' => 'E',
        'К' => 'K',
        'М' => 'M',
        'Н' => 'H',
        'О' => 'O',
        'Р' => 'P',
        'С' => 'C',
        'Т' => 'T',
        'У' => 'Y',
        'Х' => 'X',
    ];
    foreach ($map as $from => $to) {
        $expr = "REPLACE({$expr}, '{$from}', '{$to}')";
    }
    return $expr;
}

function normalize_car_number(string $number): string
{
    return normalize_plate($number);
}
