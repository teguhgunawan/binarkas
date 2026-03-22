<?php
$page = $page ?? 'dashboard';
$config = $config ?? config('app');
$authUser = $authUser ?? ($_SESSION['auth_user'] ?? null);
$summary = $summary ?? [];
$accounts = $accounts ?? [];
$categories = $categories ?? [];
$groupedCategories = $groupedCategories ?? [];
$categoryGroupsMeta = $categoryGroupsMeta ?? [];
$transactions = $transactions ?? [];
$budgets = $budgets ?? [];
$debts = $debts ?? [];
$users = $users ?? [];
$reportData = $reportData ?? [];
$flash = $flash ?? null;
$formErrors = $formErrors ?? [];
$formData = $formData ?? [];
$transactionFormData = $transactionFormData ?? [];
$transactionFormErrors = $transactionFormErrors ?? [];
$budgetFormData = $budgetFormData ?? [];
$budgetFormErrors = $budgetFormErrors ?? [];
$debtFormData = $debtFormData ?? [];
$debtFormErrors = $debtFormErrors ?? [];
$categoryFormData = $categoryFormData ?? [];
$categoryFormErrors = $categoryFormErrors ?? [];
$profileFormData = $profileFormData ?? [];
$profileFormErrors = $profileFormErrors ?? [];
$userFormData = $userFormData ?? [];
$userFormErrors = $userFormErrors ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($config['name'] ?? 'BINARKAS') . ' - ' . page_title($page)) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= htmlspecialchars(app_base('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php render('partials/sidebar', ['page' => $page, 'config' => $config, 'authUser' => $authUser]); ?>
    <main class="content-panel">
        <?php render('partials/topbar', ['page' => $page, 'database' => $database, 'authUser' => $authUser]); ?>
        <?php render('pages/' . $page, compact(
            'summary',
            'accounts',
            'categories',
            'groupedCategories',
            'categoryGroupsMeta',
            'transactions',
            'budgets',
            'debts',
            'users',
            'reportData',
            'flash',
            'formErrors',
            'formData',
            'transactionFormData',
            'transactionFormErrors',
            'budgetFormData',
            'budgetFormErrors',
            'debtFormData',
            'debtFormErrors',
            'categoryFormData',
            'categoryFormErrors',
            'profileFormData',
            'profileFormErrors',
            'userFormData',
            'userFormErrors',
            'authUser'
        )); ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= htmlspecialchars(app_base('assets/js/app.js')) ?>"></script>
</body>
</html>
