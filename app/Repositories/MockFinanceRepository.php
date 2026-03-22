<?php

class MockFinanceRepository
{
    private array $data;
    private array $mockBooks = [
        ['id' => 1, 'name' => 'Global Ledger', 'code' => 'GLOBAL', 'owner_type' => 'mixed', 'is_active' => true, 'is_default' => true],
    ];

    public function __construct()
    {
        $this->data = require dirname(__DIR__, 2) . '/data/app.php';
    }

    public function dashboardSummary(): array
    {
        return $this->data['summary'];
    }

    public function booksForUser(int $userId): array
    {
        return $this->mockBooks;
    }

    public function accounts(): array
    {
        return array_map(static fn (array $account, int $index): array => [
            'id' => 'mock-' . ($index + 1),
            'book_id' => $account['book_id'] ?? 1,
            'account_name' => $account['account_name'] ?? $account['name'],
            'reference_number' => $account['reference_number'] ?? '',
            'icon' => $account['icon'] ?? '',
            'description' => $account['description'] ?? '',
            'is_active' => $account['is_active'] ?? true,
        ] + $account, $this->data['accounts'], array_keys($this->data['accounts']));
    }

    public function createAccount(array $payload): array { throw new RuntimeException('Database schema is not ready for account writes.'); }
    public function updateAccount(int $accountId, array $payload): bool { throw new RuntimeException('Database schema is not ready for account writes.'); }
    public function deleteAccount(int $accountId): bool { throw new RuntimeException('Database schema is not ready for account writes.'); }

    public function categories(): array
    {
        return [
            ['id' => 'mock-cat-1', 'book_id' => 1, 'name' => 'Pendapatan Gaji', 'type' => 'income', 'group_name' => 'income', 'icon' => '', 'is_active' => true, 'sort_order' => 10],
            ['id' => 'mock-cat-2', 'book_id' => 1, 'name' => 'General Savings', 'type' => 'expense', 'group_name' => 'saving', 'icon' => '', 'is_active' => true, 'sort_order' => 10],
            ['id' => 'mock-cat-3', 'book_id' => 1, 'name' => 'Saham', 'type' => 'expense', 'group_name' => 'investment', 'icon' => '', 'is_active' => true, 'sort_order' => 10],
            ['id' => 'mock-cat-4', 'book_id' => 1, 'name' => 'Food', 'type' => 'expense', 'group_name' => 'expense', 'icon' => '', 'is_active' => true, 'sort_order' => 10],
            ['id' => 'mock-cat-5', 'book_id' => 1, 'name' => 'Bayar Kartu Kredit', 'type' => 'expense', 'group_name' => 'debt_payoff', 'icon' => '', 'is_active' => true, 'sort_order' => 10],
            ['id' => 'mock-cat-6', 'book_id' => 1, 'name' => 'Transfer', 'type' => 'transfer', 'group_name' => 'transfer', 'icon' => '', 'is_active' => true, 'sort_order' => 10],
        ];
    }

    public function createCategory(array $payload): array { throw new RuntimeException('Database schema is not ready for category writes.'); }
    public function updateCategory(int $categoryId, array $payload): bool { throw new RuntimeException('Database schema is not ready for category writes.'); }
    public function deleteCategory(int $categoryId): bool { throw new RuntimeException('Database schema is not ready for category writes.'); }

    public function transactions(): array
    {
        return array_map(static fn (array $item, int $index): array => ['id' => 'mock-txn-' . ($index + 1), 'book_id' => $item['book_id'] ?? 1, 'amount' => (float) $item['amount']] + $item, $this->data['transactions'], array_keys($this->data['transactions']));
    }

    public function createTransaction(array $payload): array { throw new RuntimeException('Database schema is not ready for transaction writes.'); }
    public function updateTransaction(int $transactionId, array $payload): bool { throw new RuntimeException('Database schema is not ready for transaction writes.'); }
    public function deleteTransaction(int $transactionId): bool { throw new RuntimeException('Database schema is not ready for transaction writes.'); }

    public function budgets(): array
    {
        return array_map(static fn (array $item, int $index): array => ['id' => 'mock-budget-' . ($index + 1), 'book_id' => $item['book_id'] ?? 1] + $item, $this->data['budgets'], array_keys($this->data['budgets']));
    }

    public function createBudget(array $payload): array { throw new RuntimeException('Database schema is not ready for budget writes.'); }
    public function updateBudget(int $budgetId, array $payload): bool { throw new RuntimeException('Database schema is not ready for budget writes.'); }
    public function deleteBudget(int $budgetId): bool { throw new RuntimeException('Database schema is not ready for budget writes.'); }

    public function debts(): array
    {
        return array_map(static fn (array $item, int $index): array => ['id' => 'mock-debt-' . ($index + 1), 'book_id' => $item['book_id'] ?? 1] + $item, $this->data['debts'], array_keys($this->data['debts']));
    }

    public function createDebt(array $payload): array { throw new RuntimeException('Database schema is not ready for debt writes.'); }
    public function updateDebt(int $debtId, array $payload): bool { throw new RuntimeException('Database schema is not ready for debt writes.'); }
    public function deleteDebt(int $debtId): bool { throw new RuntimeException('Database schema is not ready for debt writes.'); }
}
