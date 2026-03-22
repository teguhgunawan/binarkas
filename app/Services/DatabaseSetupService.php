<?php

class DatabaseSetupService
{
    public function __construct(private readonly Database $database)
    {
    }

    public function status(): array
    {
        if (!$this->database->isConnected()) {
            return ['connected' => false, 'schemaReady' => false, 'empty' => false, 'pendingMigrations' => [], 'error' => $this->database->lastError()];
        }

        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO) {
            return ['connected' => false, 'schemaReady' => false, 'empty' => false, 'pendingMigrations' => [], 'error' => $this->database->lastError()];
        }

        $required = ['app_migrations', 'accounts', 'transactions', 'budgets', 'debts', 'users', 'categories'];
        $schemaReady = array_reduce($required, fn (bool $carry, string $table): bool => $carry && $this->tableExists($pdo, $table), true);
        $pendingMigrations = $schemaReady ? $this->pendingMigrations($pdo) : array_column($this->definitions(), 'id');

        return ['connected' => true, 'schemaReady' => $schemaReady, 'empty' => !$schemaReady, 'pendingMigrations' => $pendingMigrations, 'error' => null];
    }

    public function runMigrations(): array
    {
        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO) {
            return ['ok' => false, 'message' => $this->database->lastError() ?? 'Database connection failed.'];
        }

        try {
            $pdo->beginTransaction();
            $pdo->exec('CREATE TABLE IF NOT EXISTS app_migrations (id VARCHAR(120) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)');
            $executed = [];

            foreach ($this->definitions() as $migration) {
                if ($this->migrationApplied($pdo, $migration['id'])) {
                    continue;
                }
                foreach ($migration['statements'] as $statement) {
                    $pdo->exec($statement);
                }
                $insert = $pdo->prepare('INSERT INTO app_migrations (id) VALUES (:id)');
                $insert->execute(['id' => $migration['id']]);
                $executed[] = $migration['id'];
            }

            $pdo->commit();
            return ['ok' => true, 'message' => empty($executed) ? 'No pending migrations.' : ('Applied migrations: ' . implode(', ', $executed))];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
    }

    private function definitions(): array
    {
        return [
            [
                'id' => '20260322_0001_core_tables',
                'statements' => [
                    "CREATE TABLE IF NOT EXISTS accounts (id BIGSERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, type VARCHAR(50) NOT NULL, balance NUMERIC(18,2) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)",
                    "CREATE TABLE IF NOT EXISTS transactions (id BIGSERIAL PRIMARY KEY, transaction_date DATE NOT NULL, title VARCHAR(150) NOT NULL, category VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, amount NUMERIC(18,2) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)",
                    "CREATE TABLE IF NOT EXISTS budgets (id BIGSERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, allocated NUMERIC(18,2) NOT NULL DEFAULT 0, used NUMERIC(18,2) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)",
                    "CREATE TABLE IF NOT EXISTS debts (id BIGSERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL, due_label VARCHAR(120) NOT NULL, amount NUMERIC(18,2) NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'safe', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)",
                ],
            ],
            [
                'id' => '20260322_0002_seed_baseline',
                'statements' => [
                    "INSERT INTO accounts (name, type, balance) SELECT 'BCA', 'Bank', 24500000 WHERE NOT EXISTS (SELECT 1 FROM accounts)",
                    "INSERT INTO accounts (name, type, balance) SELECT 'Cash', 'Cash', 1850000 WHERE NOT EXISTS (SELECT 1 FROM accounts WHERE name = 'Cash')",
                    "INSERT INTO accounts (name, type, balance) SELECT 'OVO', 'E-Wallet', 725000 WHERE NOT EXISTS (SELECT 1 FROM accounts WHERE name = 'OVO')",
                    "INSERT INTO transactions (transaction_date, title, category, type, amount) SELECT DATE '2026-03-21', 'Makan Siang', 'Food', 'expense', 45000 WHERE NOT EXISTS (SELECT 1 FROM transactions)",
                    "INSERT INTO transactions (transaction_date, title, category, type, amount) SELECT DATE '2026-03-21', 'Gaji Bulanan', 'Salary', 'income', 15000000 WHERE NOT EXISTS (SELECT 1 FROM transactions WHERE title = 'Gaji Bulanan')",
                    "INSERT INTO budgets (name, allocated, used) SELECT 'Food', 2500000, 1850000 WHERE NOT EXISTS (SELECT 1 FROM budgets)",
                    "INSERT INTO budgets (name, allocated, used) SELECT 'Transport', 1200000, 640000 WHERE NOT EXISTS (SELECT 1 FROM budgets WHERE name = 'Transport')",
                    "INSERT INTO debts (name, due_label, amount, status) SELECT 'BCA Visa', '3 hari lagi', 1250000, 'warning' WHERE NOT EXISTS (SELECT 1 FROM debts)",
                ],
            ],
            [
                'id' => '20260322_0003_auth_users',
                'statements' => [
                    "CREATE TABLE IF NOT EXISTS users (id BIGSERIAL PRIMARY KEY, full_name VARCHAR(120) NOT NULL, email VARCHAR(191) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role VARCHAR(30) NOT NULL DEFAULT 'admin', is_active BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)",
                ],
            ],
            [
                'id' => '20260322_0004_categories',
                'statements' => [
                    "CREATE TABLE IF NOT EXISTS categories (id BIGSERIAL PRIMARY KEY, name VARCHAR(120) NOT NULL UNIQUE, type VARCHAR(20) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)",
                    "INSERT INTO categories (name, type) SELECT 'Salary', 'income' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Salary')",
                    "INSERT INTO categories (name, type) SELECT 'Food', 'expense' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Food')",
                    "INSERT INTO categories (name, type) SELECT 'Transfer', 'transfer' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Transfer')",
                    "INSERT INTO categories (name, type) SELECT 'Utilities', 'expense' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Utilities')",
                ],
            ],
            [
                'id' => '20260322_0005_setup_metadata',
                'statements' => [
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'accounts' AND column_name = 'account_name') THEN ALTER TABLE accounts ADD COLUMN account_name VARCHAR(150); END IF; END $$",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'accounts' AND column_name = 'reference_number') THEN ALTER TABLE accounts ADD COLUMN reference_number VARCHAR(100); END IF; END $$",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'accounts' AND column_name = 'icon') THEN ALTER TABLE accounts ADD COLUMN icon VARCHAR(20); END IF; END $$",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'accounts' AND column_name = 'is_active') THEN ALTER TABLE accounts ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE; END IF; END $$",
                    "UPDATE accounts SET account_name = name WHERE account_name IS NULL OR account_name = ''",
                    "UPDATE accounts SET reference_number = '' WHERE reference_number IS NULL",
                    "UPDATE accounts SET icon = '' WHERE icon IS NULL",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'categories' AND column_name = 'group_name') THEN ALTER TABLE categories ADD COLUMN group_name VARCHAR(40) NOT NULL DEFAULT 'expense'; END IF; END $$",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'categories' AND column_name = 'icon') THEN ALTER TABLE categories ADD COLUMN icon VARCHAR(20); END IF; END $$",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'categories' AND column_name = 'is_active') THEN ALTER TABLE categories ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT TRUE; END IF; END $$",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'categories' AND column_name = 'sort_order') THEN ALTER TABLE categories ADD COLUMN sort_order INTEGER NOT NULL DEFAULT 0; END IF; END $$",
                    "UPDATE categories SET icon = '' WHERE icon IS NULL",
                    "UPDATE categories SET group_name = CASE WHEN type = 'income' THEN 'income' WHEN type = 'transfer' THEN 'transfer' ELSE 'expense' END WHERE group_name IS NULL OR group_name = ''"
                ],
            ],
            [
                'id' => '20260322_0006_accounts_transactions_refinement',
                'statements' => [
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'accounts' AND column_name = 'description') THEN ALTER TABLE accounts ADD COLUMN description TEXT; END IF; END $$",
                    "UPDATE accounts SET description = '' WHERE description IS NULL",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'transactions' AND column_name = 'account_name') THEN ALTER TABLE transactions ADD COLUMN account_name VARCHAR(150) NOT NULL DEFAULT ''; END IF; END $$",
                    "UPDATE transactions SET account_name = COALESCE((SELECT name FROM accounts ORDER BY id ASC LIMIT 1), 'Unassigned') WHERE account_name IS NULL OR account_name = ''"
                ],
            ],
            [
                'id' => '20260322_0007_transaction_pairing',
                'statements' => [
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'transactions' AND column_name = 'paired_account_name') THEN ALTER TABLE transactions ADD COLUMN paired_account_name VARCHAR(150) NOT NULL DEFAULT ''; END IF; END $$",
                    "DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'transactions' AND column_name = 'paired_category') THEN ALTER TABLE transactions ADD COLUMN paired_category VARCHAR(120) NOT NULL DEFAULT ''; END IF; END $$",
                    "UPDATE transactions SET paired_account_name = '' WHERE paired_account_name IS NULL",
                    "UPDATE transactions SET paired_category = '' WHERE paired_category IS NULL"
                ],
            ],
        ];
    }

    private function pendingMigrations(PDO $pdo): array
    {
        return array_values(array_filter(array_column($this->definitions(), 'id'), fn (string $id) => !$this->migrationApplied($pdo, $id)));
    }

    private function migrationApplied(PDO $pdo, string $id): bool
    {
        $statement = $pdo->prepare('SELECT 1 FROM app_migrations WHERE id = :id');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $statement = $pdo->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = :table)");
        $statement->execute(['table' => $table]);
        return (bool) $statement->fetchColumn();
    }
}
