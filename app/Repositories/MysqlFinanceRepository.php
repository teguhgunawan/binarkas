<?php

class MysqlFinanceRepository
{
    private array $columnCache = [];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function dashboardSummary(): array
    {
        $netWorthSql = 'SELECT COALESCE(SUM(balance), 0) FROM accounts';
        if ($this->hasColumn('accounts', 'is_active')) {
            $netWorthSql .= ' WHERE is_active = TRUE';
        }
        $netWorth = (float) ($this->scalar($netWorthSql) ?? 0);
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
        $accountNameExpr = $this->hasColumn('accounts', 'account_name') ? 'COALESCE(account_name, name)' : 'name';
        $referenceExpr = $this->hasColumn('accounts', 'reference_number') ? "COALESCE(reference_number, '')" : "''";
        $iconExpr = $this->hasColumn('accounts', 'icon') ? "COALESCE(icon, '')" : "''";
        $descriptionExpr = $this->hasColumn('accounts', 'description') ? "COALESCE(description, '')" : "''";
        $isActiveExpr = $this->hasColumn('accounts', 'is_active') ? 'is_active' : 'TRUE';
        $orderBy = $this->hasColumn('accounts', 'is_active') ? 'is_active DESC, type ASC, name ASC' : 'type ASC, name ASC';
        $sql = "SELECT id, name, {$accountNameExpr} AS account_name, type, {$referenceExpr} AS reference_number, {$iconExpr} AS icon, {$descriptionExpr} AS description, {$isActiveExpr} AS is_active, balance FROM accounts ORDER BY {$orderBy}";
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public function createAccount(array $payload): array
    {
        $columns = ['name', 'type', 'balance'];
        $placeholders = [':name', ':type', ':balance'];
        $params = [
            'name' => $payload['name'],
            'type' => $payload['type'],
            'balance' => $payload['balance'],
        ];

        if ($this->hasColumn('accounts', 'account_name')) {
            $columns[] = 'account_name';
            $placeholders[] = ':account_name';
            $params['account_name'] = $payload['account_name'];
        }
        if ($this->hasColumn('accounts', 'reference_number')) {
            $columns[] = 'reference_number';
            $placeholders[] = ':reference_number';
            $params['reference_number'] = $payload['reference_number'];
        }
        if ($this->hasColumn('accounts', 'icon')) {
            $columns[] = 'icon';
            $placeholders[] = ':icon';
            $params['icon'] = $payload['icon'];
        }
        if ($this->hasColumn('accounts', 'description')) {
            $columns[] = 'description';
            $placeholders[] = ':description';
            $params['description'] = $payload['description'] ?? '';
        }
        if ($this->hasColumn('accounts', 'is_active')) {
            $columns[] = 'is_active';
            $placeholders[] = ':is_active';
            $params['is_active'] = !empty($payload['is_active']) ? 'true' : 'false';
        }

        $sql = 'INSERT INTO accounts (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return [];
    }

    public function updateAccount(int $accountId, array $payload): bool
    {
        $sets = ['name = :name', 'type = :type', 'balance = :balance'];
        $params = [
            'id' => $accountId,
            'name' => $payload['name'],
            'type' => $payload['type'],
            'balance' => $payload['balance'],
        ];

        if ($this->hasColumn('accounts', 'account_name')) {
            $sets[] = 'account_name = :account_name';
            $params['account_name'] = $payload['account_name'];
        }
        if ($this->hasColumn('accounts', 'reference_number')) {
            $sets[] = 'reference_number = :reference_number';
            $params['reference_number'] = $payload['reference_number'];
        }
        if ($this->hasColumn('accounts', 'icon')) {
            $sets[] = 'icon = :icon';
            $params['icon'] = $payload['icon'];
        }
        if ($this->hasColumn('accounts', 'description')) {
            $sets[] = 'description = :description';
            $params['description'] = $payload['description'] ?? '';
        }
        if ($this->hasColumn('accounts', 'is_active')) {
            $sets[] = 'is_active = :is_active';
            $params['is_active'] = !empty($payload['is_active']) ? 'true' : 'false';
        }

        $sql = 'UPDATE accounts SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
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
        $groupNameExpr = $this->hasColumn('categories', 'group_name')
            ? "COALESCE(group_name, CASE WHEN type = 'income' THEN 'income' WHEN type = 'transfer' THEN 'transfer' ELSE 'expense' END)"
            : "CASE WHEN type = 'income' THEN 'income' WHEN type = 'transfer' THEN 'transfer' ELSE 'expense' END";
        $iconExpr = $this->hasColumn('categories', 'icon') ? "COALESCE(icon, '')" : "''";
        $activeExpr = $this->hasColumn('categories', 'is_active') ? 'is_active' : 'TRUE';
        $sortExpr = $this->hasColumn('categories', 'sort_order') ? 'sort_order' : '0';
        $orderBy = $this->hasColumn('categories', 'group_name') ? 'group_name ASC, ' : '';
        $orderBy .= $this->hasColumn('categories', 'sort_order') ? 'sort_order ASC, ' : '';
        $orderBy .= 'name ASC';
        $sql = "SELECT id, name, type, {$groupNameExpr} AS group_name, {$iconExpr} AS icon, {$activeExpr} AS is_active, {$sortExpr} AS sort_order FROM categories ORDER BY {$orderBy}";
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public function createCategory(array $payload): array
    {
        $columns = ['name', 'type'];
        $placeholders = [':name', ':type'];
        $params = ['name' => $payload['name'], 'type' => $payload['type']];

        if ($this->hasColumn('categories', 'group_name')) {
            $columns[] = 'group_name';
            $placeholders[] = ':group_name';
            $params['group_name'] = $payload['group_name'];
        }
        if ($this->hasColumn('categories', 'icon')) {
            $columns[] = 'icon';
            $placeholders[] = ':icon';
            $params['icon'] = $payload['icon'];
        }
        if ($this->hasColumn('categories', 'is_active')) {
            $columns[] = 'is_active';
            $placeholders[] = ':is_active';
            $params['is_active'] = !empty($payload['is_active']) ? 'true' : 'false';
        }
        if ($this->hasColumn('categories', 'sort_order')) {
            $columns[] = 'sort_order';
            $placeholders[] = ':sort_order';
            $params['sort_order'] = $payload['sort_order'];
        }

        $statement = $this->pdo->prepare('INSERT INTO categories (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $statement->execute($params);
        return [];
    }

    public function updateCategory(int $categoryId, array $payload): bool
    {
        $sets = ['name = :name', 'type = :type'];
        $params = ['id' => $categoryId, 'name' => $payload['name'], 'type' => $payload['type']];

        if ($this->hasColumn('categories', 'group_name')) {
            $sets[] = 'group_name = :group_name';
            $params['group_name'] = $payload['group_name'];
        }
        if ($this->hasColumn('categories', 'icon')) {
            $sets[] = 'icon = :icon';
            $params['icon'] = $payload['icon'];
        }
        if ($this->hasColumn('categories', 'is_active')) {
            $sets[] = 'is_active = :is_active';
            $params['is_active'] = !empty($payload['is_active']) ? 'true' : 'false';
        }
        if ($this->hasColumn('categories', 'sort_order')) {
            $sets[] = 'sort_order = :sort_order';
            $params['sort_order'] = $payload['sort_order'];
        }

        $statement = $this->pdo->prepare('UPDATE categories SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $statement->execute($params);
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
        $accountExpr = $this->hasColumn('transactions', 'account_name') ? "COALESCE(account_name, '')" : "''";
        $sql = "SELECT id, transaction_date AS date, title, {$accountExpr} AS account_name, category, type, amount FROM transactions ORDER BY transaction_date DESC, id DESC";
        return $this->pdo->query($sql)->fetchAll() ?: [];
    }

    public function createTransaction(array $payload): array
    {
        $columns = ['transaction_date', 'title', 'category', 'type', 'amount'];
        $placeholders = [':date', ':title', ':category', ':type', ':amount'];
        $params = [
            'date' => $payload['date'],
            'title' => $payload['title'],
            'category' => $payload['category'],
            'type' => $payload['type'],
            'amount' => $payload['amount'],
        ];

        if ($this->hasColumn('transactions', 'account_name')) {
            $columns[] = 'account_name';
            $placeholders[] = ':account_name';
            $params['account_name'] = $payload['account_name'];
        }

        $sql = 'INSERT INTO transactions (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return [];
    }

    public function updateTransaction(int $transactionId, array $payload): bool
    {
        $sets = ['transaction_date = :date', 'title = :title', 'category = :category', 'type = :type', 'amount = :amount'];
        if ($this->hasColumn('transactions', 'account_name')) {
            $sets[] = 'account_name = :account_name';
        }
        $sql = 'UPDATE transactions SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $statement = $this->pdo->prepare($sql);
        $params = [
            'id' => $transactionId,
            'date' => $payload['date'],
            'title' => $payload['title'],
            'category' => $payload['category'],
            'type' => $payload['type'],
            'amount' => $payload['amount'],
        ];
        if ($this->hasColumn('transactions', 'account_name')) {
            $params['account_name'] = $payload['account_name'];
        }
        $statement->execute($params);
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

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        $statement = $this->pdo->prepare(
            "SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = current_schema()
                  AND table_name = :table
                  AND column_name = :column
            )"
        );
        $statement->execute(['table' => $table, 'column' => $column]);
        $exists = (bool) $statement->fetchColumn();
        $this->columnCache[$key] = $exists;
        return $exists;
    }
}
