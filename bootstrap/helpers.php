<?php

require_once __DIR__ . '/Env.php';

Env::load(dirname(__DIR__) . '/.env');

function env_value(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value === false || $value === null || $value === '' ? $default : (string) $value;
}

function config(string $key)
{
    static $config = [];

    $segments = explode('.', $key);
    $file = array_shift($segments);

    if (!isset($config[$file])) {
        $path = dirname(__DIR__) . '/config/' . $file . '.php';
        $config[$file] = is_file($path) ? require $path : [];
    }

    $value = $config[$file] ?? null;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return null;
        }
        $value = $value[$segment];
    }

    return $value;
}

function app_base(string $path = ''): string
{
    $base = rtrim((string) config('app.base_path'), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function nav_items(): array
{
    return [
        'dashboard' => 'Dashboard',
        'transactions' => 'Transactions',
        'accounts' => 'Accounts',
        'budgets' => 'Budgets',
        'debts' => 'Debts',
        'reports' => 'Reports',
        'user-management' => 'Manajemen User',
        'account-management' => 'Manajemen Akun',
        'category-management' => 'Manajemen Kategori',
    ];
}

function page_title(string $page): string
{
    return match ($page) {
        'transactions' => 'Transactions',
        'accounts' => 'Accounts',
        'budgets' => 'Budgets',
        'debts' => 'Debts',
        'reports' => 'Reports',
        'user-management' => 'Manajemen User',
        'account-management' => 'Manajemen Akun',
        'category-management' => 'Manajemen Kategori',
        'setup' => 'Database Setup',
        default => 'Dashboard',
    };
}

function account_type_options(): array
{
    return ['Cash', 'Bank', 'E-Wallet', 'Investment Cash', 'Store Cashbox', 'Other'];
}

function transaction_type_options(): array
{
    return ['income', 'expense', 'transfer'];
}

function category_setup_groups(): array
{
    return [
        'income' => ['label' => 'Income', 'hint' => 'Budgeting & Spending', 'default_type' => 'income', 'table_class' => 'setup-table-income'],
        'saving' => ['label' => 'Saving', 'hint' => 'Budgeting & Spending', 'default_type' => 'expense', 'table_class' => 'setup-table-saving'],
        'investment' => ['label' => 'Investment', 'hint' => 'Budgeting & Spending', 'default_type' => 'expense', 'table_class' => 'setup-table-investment'],
        'expense' => ['label' => 'Expense', 'hint' => 'Budgeting & Spending', 'default_type' => 'expense', 'table_class' => 'setup-table-expense'],
        'debt_payoff' => ['label' => 'Debt Payoff', 'hint' => 'Debts', 'default_type' => 'expense', 'table_class' => 'setup-table-debt'],
        'transfer' => ['label' => 'Transfer / System', 'hint' => 'Internal Flow', 'default_type' => 'transfer', 'table_class' => 'setup-table-transfer'],
    ];
}

function debt_status_options(): array
{
    return ['safe', 'warning', 'danger'];
}

function format_idr(int|float $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function transaction_badge_class(string $type): string
{
    return match ($type) {
        'income' => 'success',
        'expense' => 'danger',
        'transfer' => 'primary',
        default => 'secondary',
    };
}

function debt_badge_class(string $status): string
{
    return match ($status) {
        'warning' => 'warning text-dark',
        'danger' => 'danger',
        default => 'success',
    };
}

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/app/Views/' . $view . '.php';
}
