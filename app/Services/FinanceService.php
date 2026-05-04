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
            'debtFormData' => [
                'name' => '', 'debt_type' => 'credit_card', 'due' => '', 'amount' => '0', 'status' => 'safe',
                'cc_last_four' => '', 'cc_expiry' => '', 'cc_billing_day' => '', 'cc_due_day' => '',
                'loan_principal' => '0', 'loan_installment' => '0', 'loan_tenure_months' => '',
                'loan_paid_months' => '0', 'loan_due_day' => '', 'interest_rate' => '',
                'od_usage_start_date' => '', 'od_due_date' => '',
            ],
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
        $name      = trim((string) ($input['name'] ?? ''));
        $type      = trim((string) ($input['debt_type'] ?? 'general'));
        $amountRaw = str_replace([',', ' '], '', (string) ($input['amount'] ?? '0'));
        $status    = trim((string) ($input['status'] ?? 'safe'));
        $errors    = [];

        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Nama wajib diisi minimal 2 karakter.';
        }
        if (!array_key_exists($type, debt_type_options())) {
            $errors['debt_type'] = 'Tipe hutang tidak valid.';
        }
        if (!is_numeric($amountRaw) || (float) $amountRaw < 0) {
            $errors['amount'] = 'Nominal harus angka non-negatif.';
        }
        if (!in_array($status, debt_status_options(), true)) {
            $errors['status'] = 'Status tidak valid.';
        }

        $payload = [
            'name' => $name, 'debt_type' => $type, 'amount' => (float) $amountRaw, 'status' => $status,
            'due' => '',
            'cc_last_four' => null, 'cc_expiry' => null, 'cc_billing_day' => null, 'cc_due_day' => null,
            'loan_principal' => null, 'loan_installment' => null, 'loan_tenure_months' => null,
            'loan_paid_months' => null, 'loan_due_day' => null,
            'interest_rate' => null,
            'od_usage_start_date' => null, 'od_due_date' => null,
        ];

        if ($type === 'credit_card') {
            $lastFour   = trim((string) ($input['cc_last_four'] ?? ''));
            $expiry     = trim((string) ($input['cc_expiry'] ?? ''));
            $billingDay = trim((string) ($input['cc_billing_day'] ?? ''));
            $dueDay     = trim((string) ($input['cc_due_day'] ?? ''));

            if (!preg_match('/^\d{4}$/', $lastFour)) {
                $errors['cc_last_four'] = '4 digit terakhir nomor kartu wajib diisi.';
            }
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{4}$/', $expiry)) {
                $errors['cc_expiry'] = 'Format masa aktif: MM/YYYY (contoh: 08/2029).';
            }
            if (!ctype_digit($billingDay) || (int) $billingDay < 1 || (int) $billingDay > 31) {
                $errors['cc_billing_day'] = 'Tanggal cetak tagihan harus 1–31.';
            }
            if (!ctype_digit($dueDay) || (int) $dueDay < 1 || (int) $dueDay > 31) {
                $errors['cc_due_day'] = 'Tanggal jatuh tempo harus 1–31.';
            }

            $payload['due']            = "Jatuh tempo tgl {$dueDay} tiap bulan";
            $payload['cc_last_four']   = $lastFour;
            $payload['cc_expiry']      = $expiry;
            $payload['cc_billing_day'] = ctype_digit($billingDay) ? (int) $billingDay : null;
            $payload['cc_due_day']     = ctype_digit($dueDay) ? (int) $dueDay : null;

        } elseif ($type === 'installment') {
            $principalRaw   = str_replace([',', ' '], '', (string) ($input['loan_principal'] ?? '0'));
            $installmentRaw = str_replace([',', ' '], '', (string) ($input['loan_installment'] ?? '0'));
            $tenureRaw      = trim((string) ($input['loan_tenure_months'] ?? ''));
            $paidRaw        = trim((string) ($input['loan_paid_months'] ?? '0'));
            $dueDayRaw      = trim((string) ($input['loan_due_day'] ?? ''));
            $rateRaw        = str_replace([',', ' '], '', (string) ($input['interest_rate'] ?? ''));

            if (!is_numeric($principalRaw) || (float) $principalRaw <= 0) {
                $errors['loan_principal'] = 'Pokok pinjaman harus lebih dari 0.';
            }
            if (!is_numeric($installmentRaw) || (float) $installmentRaw <= 0) {
                $errors['loan_installment'] = 'Angsuran bulanan harus lebih dari 0.';
            }
            if (!ctype_digit($tenureRaw) || (int) $tenureRaw < 1) {
                $errors['loan_tenure_months'] = 'Tenor harus bilangan bulat positif.';
            }
            if (!ctype_digit($paidRaw) || (int) $paidRaw < 0) {
                $errors['loan_paid_months'] = 'Bulan dibayar tidak valid.';
            }
            if (!ctype_digit($dueDayRaw) || (int) $dueDayRaw < 1 || (int) $dueDayRaw > 31) {
                $errors['loan_due_day'] = 'Tanggal jatuh tempo cicilan harus 1–31.';
            }
            if ($rateRaw !== '' && (!is_numeric($rateRaw) || (float) $rateRaw < 0)) {
                $errors['interest_rate'] = 'Suku bunga harus angka non-negatif.';
            }

            $remaining = max(0, (int) $tenureRaw - (int) $paidRaw);
            $payload['due']                = "Angsuran tgl {$dueDayRaw}, sisa {$remaining} bln";
            $payload['loan_principal']     = is_numeric($principalRaw) ? (float) $principalRaw : null;
            $payload['loan_installment']   = is_numeric($installmentRaw) ? (float) $installmentRaw : null;
            $payload['loan_tenure_months'] = ctype_digit($tenureRaw) ? (int) $tenureRaw : null;
            $payload['loan_paid_months']   = ctype_digit($paidRaw) ? (int) $paidRaw : null;
            $payload['loan_due_day']       = ctype_digit($dueDayRaw) ? (int) $dueDayRaw : null;
            $payload['interest_rate']      = ($rateRaw !== '' && is_numeric($rateRaw)) ? (float) $rateRaw : null;

        } elseif ($type === 'overdraft') {
            $startDate = trim((string) ($input['od_usage_start_date'] ?? ''));
            $dueDate   = trim((string) ($input['od_due_date'] ?? ''));
            $rateRaw   = str_replace([',', ' '], '', (string) ($input['interest_rate'] ?? ''));

            if (!$this->isValidDate($startDate)) {
                $errors['od_usage_start_date'] = 'Tanggal mulai pemakaian tidak valid.';
            }
            if (!$this->isValidDate($dueDate)) {
                $errors['od_due_date'] = 'Tanggal jatuh tempo tidak valid.';
            }
            if (!is_numeric($rateRaw) || (float) $rateRaw < 0) {
                $errors['interest_rate'] = 'Suku bunga tahunan harus angka non-negatif.';
            }

            $payload['due']                 = "Jatuh tempo: {$dueDate}";
            $payload['od_usage_start_date'] = $startDate;
            $payload['od_due_date']         = $dueDate;
            $payload['interest_rate']       = is_numeric($rateRaw) ? (float) $rateRaw : null;

        } else {
            $due = trim((string) ($input['due'] ?? ''));
            if (mb_strlen($due) < 2) {
                $errors['due'] = 'Due label wajib diisi minimal 2 karakter.';
            }
            $payload['due'] = $due;
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return $payload;
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
