<?php
require __DIR__ . '/bootstrap/app.php';

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = array_merge(array_keys(nav_items()), ['setup', 'login', 'logout', 'settings']);
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$database = new Database();
$authService = new AuthService($database);
$financeService = new FinanceService($database);
$authService->ensureDefaultAdmin();

if ($page === 'login') {
    if ($authService->check()) { header('Location: ' . app_base('?page=dashboard')); exit; }
    (new AuthController($authService))->login();
    return;
}
if ($page === 'logout') { (new AuthController($authService))->logout(); return; }
if (!$authService->check() && $page !== 'setup') { header('Location: ' . app_base('?page=login')); exit; }
if ($page === 'setup') { (new SetupController(new DatabaseSetupService($database)))->handle(); return; }
if ($page === 'settings') { header('Location: ' . app_base('?page=user-management')); exit; }
if ($page === 'accounts') { header('Location: ' . app_base('?page=account-management')); exit; }
if ($page === 'transactions') { (new TransactionsController($financeService))->handle(); return; }
if ($page === 'budgets') { (new BudgetsController($financeService))->handle(); return; }
if ($page === 'debts') { (new DebtsController($financeService))->handle(); return; }
if (in_array($page, ['user-management', 'account-management', 'category-management'], true)) {
    (new SettingsController($financeService, $authService))->handle($page);
    return;
}

$controller = new PageController($financeService);
$controller->show($page, ['authUser' => $authService->user()]);
