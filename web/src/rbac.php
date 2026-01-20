<?php

declare(strict_types=1);

function can_manage_users(string $role): bool
{
    return in_array($role, ['admin', 'manager'], true);
}

function can_manage_cars(string $role): bool
{
    return in_array($role, ['admin', 'manager'], true);
}

function can_view_remote(string $role): bool
{
    return $role === 'admin';
}

function require_role(array $allowed, string $role): void
{
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('Access denied');
    }
}
