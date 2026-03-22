<?php

require __DIR__ . '/helpers.php';

$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/../app/Repositories/Database.php';
require __DIR__ . '/../app/Repositories/MockFinanceRepository.php';
require __DIR__ . '/../app/Repositories/MysqlFinanceRepository.php';
require __DIR__ . '/../app/Services/DatabaseSetupService.php';
require __DIR__ . '/../app/Services/AuthService.php';
require __DIR__ . '/../app/Services/FinanceService.php';
require __DIR__ . '/../app/Controllers/PageController.php';
require __DIR__ . '/../app/Controllers/AuthController.php';
require __DIR__ . '/../app/Controllers/TransactionsController.php';
require __DIR__ . '/../app/Controllers/BudgetsController.php';
require __DIR__ . '/../app/Controllers/DebtsController.php';
require __DIR__ . '/../app/Controllers/SettingsController.php';
require __DIR__ . '/../app/Controllers/SetupController.php';
