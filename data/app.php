<?php
return [
    'app_name' => 'BINARKAS',
    'base_path' => '/binarkas',
    'currency' => 'IDR',
    'user' => [
        'name' => 'Axel',
        'role' => 'Owner',
    ],
    'summary' => [
        'netWorth' => 284500000,
        'monthlyIncome' => 18500000,
        'monthlyExpense' => 11250000,
        'budgetUsedPercent' => 68,
    ],
    'accounts' => [
        ['name' => 'BCA', 'type' => 'Bank', 'balance' => 24500000],
        ['name' => 'Cash', 'type' => 'Cash', 'balance' => 1850000],
        ['name' => 'OVO', 'type' => 'E-Wallet', 'balance' => 725000],
        ['name' => 'RDN Mandiri', 'type' => 'Investment Cash', 'balance' => 14500000],
    ],
    'transactions' => [
        ['date' => '2026-03-21', 'title' => 'Makan Siang', 'category' => 'Food', 'type' => 'expense', 'amount' => 45000],
        ['date' => '2026-03-21', 'title' => 'Gaji Bulanan', 'category' => 'Salary', 'type' => 'income', 'amount' => 15000000],
        ['date' => '2026-03-20', 'title' => 'Transfer ke OVO', 'category' => 'Transfer', 'type' => 'transfer', 'amount' => 500000],
        ['date' => '2026-03-19', 'title' => 'Listrik', 'category' => 'Utilities', 'type' => 'expense', 'amount' => 375000],
    ],
    'budgets' => [
        ['name' => 'Food', 'allocated' => 2500000, 'used' => 1850000],
        ['name' => 'Transport', 'allocated' => 1200000, 'used' => 640000],
        ['name' => 'Utilities', 'allocated' => 1500000, 'used' => 985000],
        ['name' => 'Lifestyle', 'allocated' => 2000000, 'used' => 1760000],
    ],
    'debts' => [
        ['name' => 'BCA Visa', 'due' => '3 hari lagi', 'amount' => 1250000, 'status' => 'warning'],
        ['name' => 'KPR BTN', 'due' => '28 Mar 2026', 'amount' => 3250000, 'status' => 'safe'],
    ],
];
