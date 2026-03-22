<?php

class MysqlFinanceRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function dashboardSummary(): array
    {
        $netWorth = (float) ($this->scalar('SELECT COALESCE(SUM(balance), 0) FROM accounts WHERE is_active = TRUE') ?? 0);
        $income = (float) ($this->scalar("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'income'") ?? 0);
        $expense = (float) ($this->scalar("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'expense'") ?? 0);
        $allocated = (float) ($this->scalar('SELECT COALESCE(SUM(allocated), 0) FROM budgets') ?? 0);
        $used = (float) ($this->scalar('SELECT COALESCE(SUM(used), 0) FROM budgets') ?? 0);

        return [
            'netWorth' => $netWorth,
            'monthlyIncome' => $income,
            'monthlyExpense' => $expense,
            'budgetUsedPercent' => $allocated > 0 ? (int) round(($used / $allocated) * 100) : 0,
        ];
    }

    public function accounts(): array
    {
        return $this->pdo->query("SELECT id, name, COALESCE(account_name, name) AS account_name, type, COALESCE(reference_number, '') AS reference_number, COALESCE(icon, '') AS icon, is_active, balance FROM accounts ORDER BY is_active DESC, type ASC, name ASC")->fetchAll() ?: [];
    }

    public function createAccount(array $payload): array
    {
        $statement = $this->pdo->prepare('INSERT INTO accounts (name, account_name, type, reference_number, icon, is_active, balance) VALUES (:name, :account_name, :type, :reference_number, :icon, :is_active, :balance) RETURNING id, name, account_name, type, reference_number, icon, is_active, balance');
        $statement->execute([
            'name' => $payload['name'],
            'account_name' => $payload['account_name'],
            'type' => $payload['type'],
            'reference_number' => $payload['reference_number'],
            'icon' => $payload['icon'],
            'is_active' => !empty($payload['is_active']) ? 'true' : 'false',
            'balance' => $payload['balance'],
        ]);
        return $statement->fetch() ?: [];
    }

    public function updateAccount(int $accountId, array $payload): bool
    {
        $statement = $this->pdo->prepare('UPDATE accounts SET name = :name, account_name = :account_name, type = :type, reference_number = :reference_number, icon = :icon, is_active = :is_active, balance = :balance WHERE id = :id');
        $statement->execute([
            'id' => $accountId,
            'name' => $payload['name'],
            'account_name' => $payload['account_name'],
            'type' => $payload['type'],
            'reference_number' => $payload['reference_number'],
            'icon' => $payload['icon'],
            'is_active' => !empty($payload['is_active']) ? 'true' : 'false',
            'balance' => $payload['balance'],
        ]);
        return $statement->rowCount() > 0;
    }

    public function deleteAccount(int $accountId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM accounts WHERE id = :id');
        $statement->execute(['id' => $accountId]);
        return $statement->rowCount() > 0;
    }

    public function categories(): array
    {
        return $this->pdo->query("SELECT id, name, type, COALESCE(group_name, CASE WHEN type = 'income' THEN 'income' WHEN type = 'transfer' THEN 'transfer' ELSE 'expense' END) AS group_name, COALESCE(icon, '') AS icon, is_active, sort_order FROM categories ORDER BY group_name ASC, sort_order ASC, name ASC")->fetchAll() ?: [];
    }

    public function createCategory(array $payload): array
    {
        $statement = $this->pdo->prepare('INSERT INTO categories (name, type, group_name, icon, is_active, sort_order) VALUES (:name, :type, :group_name, :icon, :is_active, :sort_order) RETURNING id, name, type, group_name, icon, is_active, sort_order');
        $statement->execute([
            'name' => $payload['name'],
            'type' => $payload['type'],
            'group_name' => $payload['group_name'],
            'icon' => $payload['icon'],
            'is_active' => !empty($payload['is_active']) ? 'true' : 'false',
            'sort_order' => $payload['sort_order'],
        ]);
        return $statement->fetch() ?: [];
    }

    public function updateCategory(int $categoryId, array $payload): bool
    {
        $statement = $this->pdo->prepare('UPDATE categories SET name = :name, type = :type, group_name = :group_name, icon = :icon, is_active = :is_active, sort_order = :sort_order WHERE id = :id');
        $statement->execute([
            'id' => $categoryId,
            'name' => $payload['name'],
            'type' => $payload['type'],
            'group_name' => $payload['group_name'],
            'icon' => $payload['icon'],
            'is_active' => !empty($payload['is_active']) ? 'true' : 'false',
            'sort_order' => $payload['sort_order'],
        ]);
        return $statement->rowCount() > 0;
    }

    public function deleteCategory(int $categoryId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
        $statement->execute(['id' => $categoryId]);
        return $statement->rowCount() > 0;
    }

    public function transactions(): array
    {
        return $this->pdo->query('SELECT id, transaction_date AS date, title, category, type, amount FROM transactions ORDER BY transaction_date DESC, id DESC LIMIT 20')->fetchAll() ?: [];
    }

    public function createTransaction(array $payload): array
    {
        $statement = $this->pdo->prepare('INSERT INTO transactions (transaction_date, title, category, type, amount) VALUES (:date, :title, :category, :type, :amount) RETURNING id, transaction_date AS date, title, category, type, amount');
        $statement->execute([
            'date' => $payload['date'],
            'title' => $payload['title'],
            'category' => $payload['category'],
            'type' => $payload['type'],
            'amount' => $payload['amount'],
        ]);
        return $statement->fetch() ?: [];
    }

    public function updateTransaction(int $transactionId, array $payload): bool
    {
        $statement = $this->pdo->prepare('UPDATE transactions SET transaction_date = :date, title = :title, category = :category, type = :type, amount = :amount WHERE id = :id');
        $statement->execute([
            'id' => $transactionId,
            'date' => $payload['date'],
            'title' => $payload['title'],
            'category' => $payload['category'],
            'type' => $payload['type'],
            'amount' => $payload['amount'],
        ]);
        return $statement->rowCount() > 0;
    }

    public function deleteTransaction(int $transactionId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM transactions WHERE id = :id');
        $statement->execute(['id' => $transactionId]);
        return $statement->rowCount() > 0;
    }

    public function budgets(): array
    {
        return $this->pdo->query('SELECT id, name, allocated, used FROM budgets ORDER BY name ASC')->fetchAll() ?: [];
    }

    public function createBudget(array $payload): array
    {
        $statement = $this->pdo->prepare('INSERT INTO budgets (name, allocated, used) VALUES (:name, :allocated, :used) RETURNING id, name, allocated, used');
        $statement->execute(['name' => $payload['name'], 'allocated' => $payload['allocated'], 'used' => $payload['used']]);
        return $statement->fetch() ?: [];
    }

    public function updateBudget(int $budgetId, array $payload): bool
    {
        $statement = $this->pdo->prepare('UPDATE budgets SET name = :name, allocated = :allocated, used = :used WHERE id = :id');
        $statement->execute(['id' => $budgetId, 'name' => $payload['name'], 'allocated' => $payload['allocated'], 'used' => $payload['used']]);
        return $statement->rowCount() > 0;
    }

    public function deleteBudget(int $budgetId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM budgets WHERE id = :id');
        $statement->execute(['id' => $budgetId]);
        return $statement->rowCount() > 0;
    }

    public function debts(): array
    {
        return $this->pdo->query('SELECT id, name, due_label AS due, amount, status FROM debts ORDER BY id DESC LIMIT 10')->fetchAll() ?: [];
    }

    public function createDebt(array $payload): array
    {
        $statement = $this->pdo->prepare('INSERT INTO debts (name, due_label, amount, status) VALUES (:name, :due, :amount, :status) RETURNING id, name, due_label AS due, amount, status');
        $statement->execute(['name' => $payload['name'], 'due' => $payload['due'], 'amount' => $payload['amount'], 'status' => $payload['status']]);
        return $statement->fetch() ?: [];
    }

    public function updateDebt(int $debtId, array $payload): bool
    {
        $statement = $this->pdo->prepare('UPDATE debts SET name = :name, due_label = :due, amount = :amount, status = :status WHERE id = :id');
        $statement->execute(['id' => $debtId, 'name' => $payload['name'], 'due' => $payload['due'], 'amount' => $payload['amount'], 'status' => $payload['status']]);
        return $statement->rowCount() > 0;
    }

    public function deleteDebt(int $debtId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM debts WHERE id = :id');
        $statement->execute(['id' => $debtId]);
        return $statement->rowCount() > 0;
    }

    private function scalar(string $sql): mixed
    {
        $statement = $this->pdo->query($sql);
        return $statement ? $statement->fetchColumn() : null;
    }
}
