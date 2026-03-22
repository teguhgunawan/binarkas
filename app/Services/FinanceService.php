<?php

class FinanceService
{
    private object $repository;
    private DatabaseSetupService $setupService;

    public function __construct(private readonly Database $database)
    {
        $this->setupService = new DatabaseSetupService($database);
        $status = $this->setupService->status();
        $pdo = $this->database->connection();
        $this->repository = ($pdo instanceof PDO && !empty($status['schemaReady'])) ? new MysqlFinanceRepository($pdo) : new MockFinanceRepository();
    }

    public function pageData(string $page, array $extra = []): array
    {
        $status = $this->setupService->status();
        $accounts = $this->repository->accounts();
        $categories = $this->repository->categories();
        $transactions = $this->repository->transactions();
        $budgets = $this->repository->budgets();
        $debts = $this->repository->debts();
        $summary = $this->repository->dashboardSummary();

        return array_merge([
            'page' => $page,
            'config' => config('app'),
            'summary' => $summary,
            'accounts' => $accounts,
            'categories' => $categories,
            'groupedCategories' => $this->groupCategories($categories),
            'categoryGroupsMeta' => category_setup_groups(),
            'transactions' => $transactions,
            'budgets' => $budgets,
            'debts' => $debts,
            'reportData' => $this->buildReportData($summary, $accounts, $transactions, $budgets, $debts),
            'database' => ['connected' => $status['connected'], 'schemaReady' => $status['schemaReady'], 'empty' => $status['empty'], 'pendingMigrations' => $status['pendingMigrations'], 'error' => $status['error']],
            'flash' => null,
            'formErrors' => [],
            'formData' => ['name' => '', 'account_name' => '', 'type' => 'Bank', 'reference_number' => '', 'icon' => '', 'is_active' => '1', 'balance' => '0'],
            'transactionFormData' => ['date' => date('Y-m-d'), 'title' => '', 'category' => '', 'type' => 'expense', 'amount' => '0'],
            'transactionFormErrors' => [],
            'budgetFormData' => ['name' => '', 'allocated' => '0', 'used' => '0'],
            'budgetFormErrors' => [],
            'debtFormData' => ['name' => '', 'due' => '', 'amount' => '0', 'status' => 'safe'],
            'debtFormErrors' => [],
            'categoryFormData' => ['name' => '', 'type' => 'expense', 'group_name' => 'expense', 'icon' => '', 'is_active' => '1', 'sort_order' => '10'],
            'categoryFormErrors' => [],
            'profileFormData' => ['full_name' => '', 'email' => '', 'current_password' => '', 'new_password' => '', 'confirm_password' => ''],
            'profileFormErrors' => [],
            'userFormData' => ['full_name' => '', 'email' => '', 'role' => 'user', 'password' => '', 'is_active' => '1'],
            'userFormErrors' => [],
        ], $extra);
    }

    private function buildReportData(array $summary, array $accounts, array $transactions, array $budgets, array $debts): array
    {
        $categoryTotals = [];
        foreach ($transactions as $transaction) {
            $category = (string) ($transaction['category'] ?? 'Uncategorized');
            $categoryTotals[$category] = ($categoryTotals[$category] ?? 0) + (float) ($transaction['amount'] ?? 0);
        }
        arsort($categoryTotals);
        $overspentBudgets = array_values(array_filter($budgets, static fn (array $budget): bool => (float) $budget['allocated'] > 0 && (float) $budget['used'] > (float) $budget['allocated']));

        return [
            'kpis' => [
                ['label' => 'Net Worth', 'value' => $summary['netWorth'] ?? 0],
                ['label' => 'Income', 'value' => $summary['monthlyIncome'] ?? 0],
                ['label' => 'Expense', 'value' => $summary['monthlyExpense'] ?? 0],
                ['label' => 'Debt Items', 'value' => count($debts)],
            ],
            'topCategories' => array_slice($categoryTotals, 0, 5, true),
            'accountBalances' => $accounts,
            'budgets' => $budgets,
            'overspentBudgets' => $overspentBudgets,
            'recentTransactions' => array_slice($transactions, 0, 10),
            'debts' => $debts,
        ];
    }

    public function createAccount(array $input): array
    {
        $this->repository->createAccount($this->validateAccountPayload($input));
        return ['ok' => true, 'message' => 'Account created successfully.'];
    }

    public function updateAccount(array $input): array
    {
        $id = (int) ($input['account_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid account selected.'];
        }
        $updated = $this->repository->updateAccount($id, $this->validateAccountPayload($input));
        return ['ok' => $updated, 'message' => $updated ? 'Account updated successfully.' : 'Account not found.'];
    }

    public function deleteAccount(array $input): array
    {
        $id = (int) ($input['account_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid account selected.'];
        }
        $deleted = $this->repository->deleteAccount($id);
        return ['ok' => $deleted, 'message' => $deleted ? 'Account deleted successfully.' : 'Account not found.'];
    }

    public function createTransaction(array $input): array
    {
        $this->repository->createTransaction($this->validateTransactionPayload($input));
        return ['ok' => true, 'message' => 'Transaction created successfully.'];
    }

    public function updateTransaction(array $input): array
    {
        $id = (int) ($input['transaction_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid transaction selected.'];
        }
        $updated = $this->repository->updateTransaction($id, $this->validateTransactionPayload($input));
        return ['ok' => $updated, 'message' => $updated ? 'Transaction updated successfully.' : 'Transaction not found.'];
    }

    public function deleteTransaction(array $input): array
    {
        $id = (int) ($input['transaction_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid transaction selected.'];
        }
        $deleted = $this->repository->deleteTransaction($id);
        return ['ok' => $deleted, 'message' => $deleted ? 'Transaction deleted successfully.' : 'Transaction not found.'];
    }

    public function createBudget(array $input): array
    {
        $this->repository->createBudget($this->validateBudgetPayload($input));
        return ['ok' => true, 'message' => 'Budget created successfully.'];
    }

    public function updateBudget(array $input): array
    {
        $id = (int) ($input['budget_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid budget selected.'];
        }
        $updated = $this->repository->updateBudget($id, $this->validateBudgetPayload($input));
        return ['ok' => $updated, 'message' => $updated ? 'Budget updated successfully.' : 'Budget not found.'];
    }

    public function deleteBudget(array $input): array
    {
        $id = (int) ($input['budget_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid budget selected.'];
        }
        $deleted = $this->repository->deleteBudget($id);
        return ['ok' => $deleted, 'message' => $deleted ? 'Budget deleted successfully.' : 'Budget not found.'];
    }

    public function createDebt(array $input): array
    {
        $this->repository->createDebt($this->validateDebtPayload($input));
        return ['ok' => true, 'message' => 'Debt created successfully.'];
    }

    public function updateDebt(array $input): array
    {
        $id = (int) ($input['debt_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid debt selected.'];
        }
        $updated = $this->repository->updateDebt($id, $this->validateDebtPayload($input));
        return ['ok' => $updated, 'message' => $updated ? 'Debt updated successfully.' : 'Debt not found.'];
    }

    public function deleteDebt(array $input): array
    {
        $id = (int) ($input['debt_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid debt selected.'];
        }
        $deleted = $this->repository->deleteDebt($id);
        return ['ok' => $deleted, 'message' => $deleted ? 'Debt deleted successfully.' : 'Debt not found.'];
    }

    public function createCategory(array $input): array
    {
        $this->repository->createCategory($this->validateCategoryPayload($input));
        return ['ok' => true, 'message' => 'Category created successfully.'];
    }

    public function updateCategory(array $input): array
    {
        $id = (int) ($input['category_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid category selected.'];
        }
        $updated = $this->repository->updateCategory($id, $this->validateCategoryPayload($input));
        return ['ok' => $updated, 'message' => $updated ? 'Category updated successfully.' : 'Category not found.'];
    }

    public function deleteCategory(array $input): array
    {
        $id = (int) ($input['category_id'] ?? 0);
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid category selected.'];
        }
        $deleted = $this->repository->deleteCategory($id);
        return ['ok' => $deleted, 'message' => $deleted ? 'Category deleted successfully.' : 'Category not found.'];
    }

    private function validateAccountPayload(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $accountName = trim((string) ($input['account_name'] ?? $name));
        $type = trim((string) ($input['type'] ?? ''));
        $referenceNumber = trim((string) ($input['reference_number'] ?? ''));
        $icon = trim((string) ($input['icon'] ?? ''));
        $balanceRaw = str_replace([',', ' '], '', (string) ($input['balance'] ?? '0'));
        $isActive = $this->normalizeActiveFlag($input['is_active'] ?? null, true);
        $errors = [];

        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Account label must be at least 2 characters.';
        }
        if (mb_strlen($accountName) < 2) {
            $errors['account_name'] = 'Account name must be at least 2 characters.';
        }
        if (!in_array($type, account_type_options(), true)) {
            $errors['type'] = 'Invalid account type.';
        }
        if (!is_numeric($balanceRaw)) {
            $errors['balance'] = 'Opening balance must be numeric.';
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return [
            'name' => $name,
            'account_name' => $accountName,
            'type' => $type,
            'reference_number' => $referenceNumber,
            'icon' => $icon,
            'is_active' => $isActive,
            'balance' => (float) $balanceRaw,
        ];
    }

    private function validateTransactionPayload(array $input): array
    {
        $date = trim((string) ($input['date'] ?? ''));
        $title = trim((string) ($input['title'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));
        $type = trim((string) ($input['type'] ?? ''));
        $amountRaw = str_replace([',', ' '], '', (string) ($input['amount'] ?? '0'));
        $errors = [];

        if (!$this->isValidDate($date)) {
            $errors['date'] = 'Date is invalid.';
        }
        if (mb_strlen($title) < 2) {
            $errors['title'] = 'Title must be at least 2 characters.';
        }
        if (mb_strlen($category) < 2) {
            $errors['category'] = 'Category must be at least 2 characters.';
        }
        if (!in_array($type, transaction_type_options(), true)) {
            $errors['type'] = 'Invalid transaction type.';
        }
        if (!$this->categoryExistsForType($category, $type)) {
            $errors['category'] = 'Choose an active category from the dropdown model.';
        }
        if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) {
            $errors['amount'] = 'Amount must be greater than zero.';
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return ['date' => $date, 'title' => $title, 'category' => $category, 'type' => $type, 'amount' => (float) $amountRaw];
    }

    private function validateBudgetPayload(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $allocatedRaw = str_replace([',', ' '], '', (string) ($input['allocated'] ?? '0'));
        $usedRaw = str_replace([',', ' '], '', (string) ($input['used'] ?? '0'));
        $errors = [];

        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Budget name must be at least 2 characters.';
        }
        if (!is_numeric($allocatedRaw) || (float) $allocatedRaw < 0) {
            $errors['allocated'] = 'Allocated must be numeric and non-negative.';
        }
        if (!is_numeric($usedRaw) || (float) $usedRaw < 0) {
            $errors['used'] = 'Used must be numeric and non-negative.';
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return ['name' => $name, 'allocated' => (float) $allocatedRaw, 'used' => (float) $usedRaw];
    }

    private function validateDebtPayload(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $due = trim((string) ($input['due'] ?? ''));
        $amountRaw = str_replace([',', ' '], '', (string) ($input['amount'] ?? '0'));
        $status = trim((string) ($input['status'] ?? 'safe'));
        $errors = [];

        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Debt name must be at least 2 characters.';
        }
        if (mb_strlen($due) < 2) {
            $errors['due'] = 'Due label must be at least 2 characters.';
        }
        if (!is_numeric($amountRaw) || (float) $amountRaw < 0) {
            $errors['amount'] = 'Amount must be numeric and non-negative.';
        }
        if (!in_array($status, debt_status_options(), true)) {
            $errors['status'] = 'Invalid debt status.';
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return ['name' => $name, 'due' => $due, 'amount' => (float) $amountRaw, 'status' => $status];
    }

    private function validateCategoryPayload(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $groupName = trim((string) ($input['group_name'] ?? 'expense'));
        $groupMeta = category_setup_groups()[$groupName] ?? null;
        $type = trim((string) ($input['type'] ?? ($groupMeta['default_type'] ?? 'expense')));
        $icon = trim((string) ($input['icon'] ?? ''));
        $sortOrderRaw = str_replace([',', ' '], '', (string) ($input['sort_order'] ?? '10'));
        $isActive = $this->normalizeActiveFlag($input['is_active'] ?? null, true);
        $errors = [];

        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Category name must be at least 2 characters.';
        }
        if ($groupMeta === null) {
            $errors['group_name'] = 'Invalid category group.';
        }
        if (!in_array($type, transaction_type_options(), true)) {
            $errors['type'] = 'Invalid category type.';
        }
        if (!is_numeric($sortOrderRaw)) {
            $errors['sort_order'] = 'Sort order must be numeric.';
        }
        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return [
            'name' => $name,
            'type' => $type,
            'group_name' => $groupName,
            'icon' => $icon,
            'is_active' => $isActive,
            'sort_order' => (int) $sortOrderRaw,
        ];
    }

    private function groupCategories(array $categories): array
    {
        $grouped = [];
        foreach (array_keys(category_setup_groups()) as $groupKey) {
            $grouped[$groupKey] = [];
        }

        foreach ($categories as $category) {
            $groupKey = (string) ($category['group_name'] ?? 'expense');
            if (!array_key_exists($groupKey, $grouped)) {
                $grouped[$groupKey] = [];
            }
            $grouped[$groupKey][] = $category;
        }

        return $grouped;
    }

    private function categoryExistsForType(string $name, string $type): bool
    {
        foreach ($this->repository->categories() as $category) {
            if (empty($category['is_active'])) {
                continue;
            }
            if (strcasecmp((string) $category['name'], $name) === 0 && (string) $category['type'] === $type) {
                return true;
            }
        }
        return false;
    }

    private function normalizeActiveFlag(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTime && $date->format('Y-m-d') === $value;
    }
}




