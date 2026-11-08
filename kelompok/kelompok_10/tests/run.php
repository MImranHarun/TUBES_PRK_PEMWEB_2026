<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

function assert_same(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

assert_same(true, user_has_role(['role' => 'admin', 'is_active' => true], 'admin'), 'active admin can access admin routes');
assert_same(false, user_has_role(['role' => 'cashier', 'is_active' => true], 'admin'), 'cashier cannot access admin routes');
assert_same(true, user_has_role(['role' => 'cashier', 'is_active' => true], ['admin', 'cashier']), 'role allowlist accepts cashier');
assert_same(false, user_has_role(['role' => 'admin', 'is_active' => false], 'admin'), 'inactive users cannot access protected routes');
assert_same(false, user_has_role(null, 'admin'), 'anonymous users do not have a role');

echo "All authentication tests passed.\n";
