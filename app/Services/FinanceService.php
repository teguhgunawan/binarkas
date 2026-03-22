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
        $bookContext = $this->resolveBookContext();
        $accounts = $this->applyBookScope($this->repository->accounts(), $bookContext);
        $categories = $this->applyBookScope($this->repository->categories(), $bookContext);
        $transactions = $this->applyBookScope($this->repository->transactions(), $bookContext);
        $budgets = $this->applyBookScope($this->repository->budgets(), $bookContext);
        $debts = $this->applyBookScope($this->repository->debts(), $bookContext);
        $summary = $this->buildScopedSummary($accounts, $transactions, $budgets);

        $defaultAccount = (string) ($accounts[0]['name'] ?? '');

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
            'users' => [],
            'books' => $bookContext['books'],
            'activeBookId' => $bookContext['activeBookId'],
            'isGlobalBookScope' => $bookContext['isGlobal'],
            'activeBookLabel' => $bookContext['label'],
            'reportData' => $this->buildReportData($summary, $accounts, $transactions, $budgets, $debts),
            'database' => ['connected' => $status['connected'], 'schemaReady' => $status['schemaReady'], 'empty' => $status['empty'], 'pendingMigrations' => $status['pendingMigrations'], 'error' => $status['error']],
            'flash' => null,
            'formErrors' => [],
            'formData' => ['name' => '', 'account_name' => '', 'type' => 'Bank', 'reference_number' => '', 'icon' => '', 'description' => '', 'is_active' => '1', 'balance' => '0'],
            'transactionFormData' => ['date' => date('Y-m-d'), 'title' => '', 'account_name' => $defaultAccount, 'category' => '', 'type' => 'expense', 'amount' => '0'],
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
            'accountLedgerFilters' => ['account' => '__all', 'date_from' => date('Y-m-01'), 'date_to' => date('Y-m-d'), 'keyword' => '', 'book_id' => $bookContext['isGlobal'] ? 'all' : (string) ($bookContext['activeBookId'] ?? '')],
            'accountLedgerRows' => $transactions,
            'accountLedgerGroupedRows' => $this->groupTransactionsByDate($transactions),
            'selectedAccountLabel' => 'Semua Akun',
        ], $extra);
    }

    public function buildAccountLedger(array $input): array
    {
        $bookContext = $this->resolveBookContext();
        $account = trim((string) ($input['account'] ?? '__all'));
        $dateFrom = trim((string) ($input['date_from'] ?? date('Y-m-01')));
        $dateTo = trim((string) ($input['date_to'] ?? date('Y-m-d')));
        $keyword = trim((string) ($input['keyword'] ?? ''));

        if (!$this->isValidDate($dateFrom)) {
            $dateFrom = date('Y-m-01');
        }
        if (!$this->isValidDate($dateTo)) {
            $dateTo = date('Y-m-d');
        }
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $sourceRows = $this->applyBookScope($this->repository->transactions(), $bookContext);
        $rows = array_values(array_filter($sourceRows, function (array $item) use ($account, $dateFrom, $dateTo, $keyword): bool {
            $txDate = (string) ($item['date'] ?? '');
            if ($txDate < $dateFrom || $txDate > $dateTo) {
                return false;
            }
            if ($account !== '__all' && strcasecmp((string) ($item['account_name'] ?? ''), $account) !== 0) {
                return false;
            }
            if ($keyword !== '') {
                $haystack = strtolower(implode(' ', [
                    (string) ($item['title'] ?? ''),
                    (string) ($item['category'] ?? ''),
                    (string) ($item['account_name'] ?? ''),
                    (string) ($item['amount'] ?? ''),
                ]));
                if (!str_contains($haystack, strtolower($keyword))) {
                    return false;
                }
            }
            return true;
        }));
        $rows = $this->enrichJournalRows($rows);

        $grouped = $this->groupTransactionsByDate($rows);

        return [
            'filters' => ['account' => $account, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'keyword' => $keyword, 'book_id' => $bookContext['isGlobal'] ? 'all' : (string) ($bookContext['activeBookId'] ?? '')],
            'rows' => $rows,
            'groupedRows' => $grouped,
            'selectedAccountLabel' => $account === '__all' ? 'Semua Akun' : $account,
        ];
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

    private function buildScopedSummary(array $accounts, array $transactions, array $budgets): array
    {
        $netWorth = 0.0;
        foreach ($accounts as $account) {
            if (!empty($account['is_active'])) {
                $netWorth += (float) ($account['balance'] ?? 0);
            }
        }

        $income = 0.0;
        $expense = 0.0;
        foreach ($transactions as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            $type = (string) ($item['type'] ?? '');
            if ($type === 'income') {
                $income += $amount;
            } elseif ($type === 'expense') {
                $expense += $amount;
            }
        }

        $allocated = 0.0;
        $used = 0.0;
        foreach ($budgets as $budget) {
            $allocated += (float) ($budget['allocated'] ?? 0);
            $used += (float) ($budget['used'] ?? 0);
        }

        return [
            'netWorth' => $netWorth,
            'monthlyIncome' => $income,
            'monthlyExpense' => $expense,
            'budgetUsedPercent' => $allocated > 0 ? (int) round(($used / $allocated) * 100) : 0,
        ];
    }

    private function resolveBookContext(): array
    {
        $userId = $this->currentUserId();
        $books = method_exists($this->repository, 'booksForUser') ? $this->repository->booksForUser($userId) : [];
        $requestedRaw = $_POST['book_id'] ?? $_GET['book_id'] ?? ($_SESSION['active_book_id'] ?? null);

        if ($books === []) {
            return [
                'books' => [],
                'activeBookId' => null,
                'writeBookId' => 0,
                'isGlobal' => true,
                'label' => 'Global (Semua Pembukuan)',
            ];
        }

        $bookIds = array_map(static fn (array $book): int => (int) ($book['id'] ?? 0), $books);
        $defaultBookId = null;
        foreach ($books as $book) {
            if (!empty($book['is_default'])) {
                $defaultBookId = (int) ($book['id'] ?? 0);
                break;
            }
        }
        if ($defaultBookId === null) {
            $defaultBookId = (int) ($books[0]['id'] ?? 0);
        }

        $isGlobal = false;
        $activeBookId = $defaultBookId;

        if ($requestedRaw === 'all' || $requestedRaw === '__all') {
            $isGlobal = true;
            $_SESSION['active_book_id'] = 'all';
        } else {
            $candidate = (int) $requestedRaw;
            if ($candidate > 0 && in_array($candidate, $bookIds, true)) {
                $activeBookId = $candidate;
            }
            $_SESSION['active_book_id'] = $activeBookId;
        }

        $label = 'Global (Semua Pembukuan)';
        if (!$isGlobal) {
            foreach ($books as $book) {
                if ((int) ($book['id'] ?? 0) === $activeBookId) {
                    $label = (string) ($book['name'] ?? 'Pembukuan');
                    break;
                }
            }
        }

        return [
            'books' => $books,
            'activeBookId' => $activeBookId,
            'writeBookId' => $activeBookId,
            'isGlobal' => $isGlobal,
            'label' => $label,
        ];
    }

    private function applyBookScope(array $rows, array $bookContext): array
    {
        if (!empty($bookContext['isGlobal']) || empty($bookContext['activeBookId'])) {
            return $rows;
        }

        $activeBookId = (int) $bookContext['activeBookId'];
        return array_values(array_filter($rows, static function (array $row) use ($activeBookId): bool {
            $rowBookId = (int) ($row['book_id'] ?? 0);
            return $rowBookId === 0 || $rowBookId === $activeBookId;
        }));
    }

    private function currentUserId(): int
    {
        $user = $_SESSION['auth_user'] ?? null;
        if (!is_array($user)) {
            return 0;
        }

        return (int) ($user['id'] ?? 0);
    }

    public function createAccount(array $input): array
    {
        $payload = $this->validateAccountPayload($input);
        $payload['book_id'] = $this->resolveBookContext()['writeBookId'];
        $this->repository->createAccount($payload);
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
        $payload = $this->validateTransactionPayload($input);
        $payload['book_id'] = $this->resolveBookContext()['writeBookId'];
        $this->repository->createTransaction($payload);
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
        $payload = $this->validateBudgetPayload($input);
        $payload['book_id'] = $this->resolveBookContext()['writeBookId'];
        $this->repository->createBudget($payload);
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
        $payload = $this->validateDebtPayload($input);
        $payload['book_id'] = $this->resolveBookContext()['writeBookId'];
        $this->repository->createDebt($payload);
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
        $payload = $this->validateCategoryPayload($input);
        $payload['book_id'] = $this->resolveBookContext()['writeBookId'];
        $this->repository->createCategory($payload);
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
        $description = trim((string) ($input['description'] ?? ''));
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
            'description' => $description,
            'is_active' => $isActive,
            'balance' => (float) $balanceRaw,
        ];
    }

    private function validateTransactionPayload(array $input): array
    {
        $date = trim((string) ($input['date'] ?? ''));
        $title = trim((string) ($input['title'] ?? ''));
        $accountName = trim((string) ($input['account_name'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));
        $pairedAccountName = trim((string) ($input['paired_account_name'] ?? ''));
        $pairedCategory = trim((string) ($input['paired_category'] ?? ''));
        $type = trim((string) ($input['type'] ?? ''));
        $amountRaw = str_replace([',', ' '], '', (string) ($input['amount'] ?? '0'));
        $errors = [];

        if (!$this->isValidDate($date)) {
            $errors['date'] = 'Date is invalid.';
        }
        if (mb_strlen($title) < 2) {
            $errors['title'] = 'Title must be at least 2 characters.';
        }
        if (mb_strlen($accountName) < 2) {
            $errors['account_name'] = 'Choose account from dropdown.';
        }
        if (!$this->accountExists($accountName)) {
            $errors['account_name'] = 'Selected account is invalid.';
        }
        if ($pairedAccountName !== '' && !$this->accountExists($pairedAccountName)) {
            $errors['paired_account_name'] = 'Paired account is invalid.';
        }
        if (mb_strlen($category) < 2) {
            $errors['category'] = 'Category must be at least 2 characters.';
        }
        if ($pairedCategory !== '' && !$this->categoryExists($pairedCategory)) {
            $errors['paired_category'] = 'Paired category is invalid.';
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

        return [
            'date' => $date,
            'title' => $title,
            'account_name' => $accountName,
            'category' => $category,
            'paired_account_name' => $pairedAccountName,
            'paired_category' => $pairedCategory,
            'type' => $type,
            'amount' => (float) $amountRaw,
        ];
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

    private function categoryExists(string $name): bool
    {
        foreach ($this->repository->categories() as $category) {
            if (empty($category['is_active'])) {
                continue;
            }
            if (strcasecmp((string) $category['name'], $name) === 0) {
                return true;
            }
        }
        return false;
    }

    private function accountExists(string $name): bool
    {
        foreach ($this->repository->accounts() as $account) {
            if (empty($account['is_active'])) {
                continue;
            }
            if (strcasecmp((string) $account['name'], $name) === 0 || strcasecmp((string) ($account['account_name'] ?? ''), $name) === 0) {
                return true;
            }
        }
        return false;
    }

    private function groupTransactionsByDate(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $key = (string) ($row['date'] ?? 'Unknown Date');
            if (!array_key_exists($key, $grouped)) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $row;
        }
        return $grouped;
    }

    private function enrichJournalRows(array $rows): array
    {
        $sorted = $rows;
        usort($sorted, static function (array $a, array $b): int {
            $dateA = (string) ($a['date'] ?? '');
            $dateB = (string) ($b['date'] ?? '');
            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }
            return (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0);
        });

        $runningByAccount = [];
        $enrichedByKey = [];

        foreach ($sorted as $row) {
            $accountName = (string) ($row['account_name'] ?? 'Unassigned');
            $amount = (float) ($row['amount'] ?? 0);
            $type = (string) ($row['type'] ?? 'expense');

            $debit = 0.0;
            $credit = 0.0;

            if ($type === 'income') {
                $credit = $amount;
            } else {
                $debit = $amount;
            }

            $delta = $credit - $debit;
            $runningByAccount[$accountName] = ($runningByAccount[$accountName] ?? 0.0) + $delta;

            $row['debit'] = $debit;
            $row['credit'] = $credit;
            $row['balance'] = $runningByAccount[$accountName];
            $row['paired_account_name'] = (string) ($row['paired_account_name'] ?? '');
            $row['paired_category'] = (string) ($row['paired_category'] ?? '');

            $enrichedByKey[$this->ledgerRowKey($row)] = $row;
        }

        $result = [];
        foreach ($rows as $row) {
            $key = $this->ledgerRowKey($row);
            $result[] = $enrichedByKey[$key] ?? $row;
        }

        return $result;
    }

    private function ledgerRowKey(array $row): string
    {
        return implode('|', [
            (string) ($row['id'] ?? ''),
            (string) ($row['date'] ?? ''),
            (string) ($row['title'] ?? ''),
            (string) ($row['account_name'] ?? ''),
            (string) ($row['amount'] ?? ''),
        ]);
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
