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
        ['book_id' => 1, 'name' => 'BCA', 'account_name' => 'BCA', 'type' => 'Bank', 'balance' => 24500000, 'description' => 'Operasional utama'],
        ['book_id' => 1, 'name' => 'Cash', 'account_name' => 'Cash', 'type' => 'Cash', 'balance' => 1850000, 'description' => 'Kas harian'],
        ['book_id' => 1, 'name' => 'OVO', 'account_name' => 'OVO', 'type' => 'E-Wallet', 'balance' => 725000, 'description' => 'Dompet digital'],
        ['book_id' => 1, 'name' => 'RDN Mandiri', 'account_name' => 'RDN Mandiri', 'type' => 'Investment Cash', 'balance' => 14500000, 'description' => 'Dana investasi'],
    ],
    'transactions' => [
        ['book_id' => 1, 'date' => '2026-03-21', 'title' => 'Makan Siang', 'account_name' => 'BCA', 'category' => 'Food', 'type' => 'expense', 'amount' => 45000],
        ['book_id' => 1, 'date' => '2026-03-21', 'title' => 'Gaji Bulanan', 'account_name' => 'BCA', 'category' => 'Salary', 'type' => 'income', 'amount' => 15000000],
        ['book_id' => 1, 'date' => '2026-03-20', 'title' => 'Transfer ke OVO', 'account_name' => 'BCA', 'category' => 'Transfer', 'type' => 'transfer', 'amount' => 500000],
        ['book_id' => 1, 'date' => '2026-03-19', 'title' => 'Listrik', 'account_name' => 'Cash', 'category' => 'Utilities', 'type' => 'expense', 'amount' => 375000],
    ],
    'budgets' => [
        ['book_id' => 1, 'name' => 'Food', 'allocated' => 2500000, 'used' => 1850000],
        ['book_id' => 1, 'name' => 'Transport', 'allocated' => 1200000, 'used' => 640000],
        ['book_id' => 1, 'name' => 'Utilities', 'allocated' => 1500000, 'used' => 985000],
        ['book_id' => 1, 'name' => 'Lifestyle', 'allocated' => 2000000, 'used' => 1760000],
    ],
    'debts' => [
        ['book_id' => 1, 'name' => 'BCA Visa', 'due' => '3 hari lagi', 'amount' => 1250000, 'status' => 'warning'],
        ['book_id' => 1, 'name' => 'KPR BTN', 'due' => '28 Mar 2026', 'amount' => 3250000, 'status' => 'safe'],
    ],
];
