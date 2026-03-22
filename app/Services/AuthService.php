<?php

class AuthService
{
    public function __construct(private readonly Database $database)
    {
    }

    public function ensureDefaultAdmin(): void
    {
        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO || !$this->usersTableExists($pdo)) {
            return;
        }

        $config = config('app.auth');
        $loginId = strtolower(trim((string) ($config['admin_email'] ?? 'admin')));
        $fullName = trim((string) ($config['admin_name'] ?? 'BINARKAS Admin'));
        $password = (string) ($config['admin_password'] ?? 'ADMIN');

        $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $loginId]);
        if ($statement->fetchColumn()) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, is_active) VALUES (:full_name, :email, :password_hash, :role, TRUE)');
        $insert->execute([
            'full_name' => $fullName,
            'email' => $loginId,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
    }

    public function attemptLogin(string $email, string $password): array
    {
        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO || !$this->usersTableExists($pdo)) {
            return ['ok' => false, 'message' => 'Authentication is not ready yet. Run setup/migrations first.'];
        }

        $this->ensureDefaultAdmin();
        $statement = $pdo->prepare('SELECT id, full_name, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        if (!$user || empty($user['is_active']) || !password_verify($password, (string) $user['password_hash'])) {
            return ['ok' => false, 'message' => 'Invalid login or password.'];
        }

        $_SESSION['auth_user'] = ['id' => (int) $user['id'], 'full_name' => (string) $user['full_name'], 'email' => (string) $user['email'], 'role' => (string) $user['role']];
        return ['ok' => true, 'message' => 'Login successful.'];
    }

    public function allUsers(): array
    {
        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO || !$this->usersTableExists($pdo)) {
            return [];
        }

        $statement = $pdo->query('SELECT id, full_name, email, role, is_active, created_at FROM users ORDER BY id ASC');
        return $statement ? ($statement->fetchAll() ?: []) : [];
    }

    public function createUser(array $input): array
    {
        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO) {
            return ['ok' => false, 'message' => 'Database is unavailable.'];
        }

        $payload = $this->validateManagedUserPayload($input, true);
        $statement = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, is_active) VALUES (:full_name, :email, :password_hash, :role, :is_active)');
        $statement->execute([
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'password_hash' => password_hash($payload['password'], PASSWORD_DEFAULT),
            'role' => $payload['role'],
            'is_active' => $payload['is_active'] ? 'true' : 'false',
        ]);

        return ['ok' => true, 'message' => 'User created successfully.'];
    }

    public function updateUser(array $input): array
    {
        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO) {
            return ['ok' => false, 'message' => 'Database is unavailable.'];
        }

        $userId = (int) ($input['user_id'] ?? 0);
        if ($userId <= 0) {
            return ['ok' => false, 'message' => 'Invalid user selected.'];
        }

        $payload = $this->validateManagedUserPayload($input, false, $userId);
        $sql = 'UPDATE users SET full_name = :full_name, email = :email, role = :role, is_active = :is_active';
        $params = [
            'id' => $userId,
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'is_active' => $payload['is_active'] ? 'true' : 'false',
        ];

        if ($payload['password'] !== null && $payload['password'] !== '') {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($payload['password'], PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE id = :id';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);

        if (($this->user()['id'] ?? null) === $userId) {
            $_SESSION['auth_user']['full_name'] = $payload['full_name'];
            $_SESSION['auth_user']['email'] = $payload['email'];
            $_SESSION['auth_user']['role'] = $payload['role'];
        }

        return ['ok' => true, 'message' => 'User updated successfully.'];
    }

    public function deleteUser(array $input): array
    {
        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO) {
            return ['ok' => false, 'message' => 'Database is unavailable.'];
        }

        $userId = (int) ($input['user_id'] ?? 0);
        if ($userId <= 0) {
            return ['ok' => false, 'message' => 'Invalid user selected.'];
        }
        if (($this->user()['id'] ?? null) === $userId) {
            return ['ok' => false, 'message' => 'You cannot delete the currently logged in user.'];
        }

        $statement = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $userId]);
        return ['ok' => $statement->rowCount() > 0, 'message' => $statement->rowCount() > 0 ? 'User deleted successfully.' : 'User not found.'];
    }

    public function updateCurrentUser(array $input): array
    {
        $user = $this->user();
        $pdo = $this->database->connection();
        if (!$user || !$pdo instanceof PDO) {
            return ['ok' => false, 'message' => 'You must be logged in.'];
        }

        $fullName = trim((string) ($input['full_name'] ?? ''));
        $loginId = strtolower(trim((string) ($input['email'] ?? '')));
        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmPassword = (string) ($input['confirm_password'] ?? '');
        $errors = [];

        if (mb_strlen($fullName) < 2) { $errors['full_name'] = 'Full name must be at least 2 characters.'; }
        if (mb_strlen($loginId) < 3) { $errors['email'] = 'Login ID must be at least 3 characters.'; }
        if ($currentPassword === '') { $errors['current_password'] = 'Current password is required.'; }
        if ($newPassword !== '' && mb_strlen($newPassword) < 6) { $errors['new_password'] = 'New password must be at least 6 characters.'; }
        if ($newPassword !== $confirmPassword) { $errors['confirm_password'] = 'Password confirmation does not match.'; }
        if ($errors !== []) { throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR)); }

        $statement = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $statement->execute(['id' => $user['id']]);
        $passwordHash = (string) $statement->fetchColumn();
        if (!password_verify($currentPassword, $passwordHash)) {
            return ['ok' => false, 'message' => 'Current password is incorrect.'];
        }

        $duplicate = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
        $duplicate->execute(['email' => $loginId, 'id' => $user['id']]);
        if ($duplicate->fetchColumn()) {
            return ['ok' => false, 'message' => 'Login ID is already used by another account.'];
        }

        $sql = 'UPDATE users SET full_name = :full_name, email = :email';
        $params = ['id' => $user['id'], 'full_name' => $fullName, 'email' => $loginId];
        if ($newPassword !== '') {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';
        $update = $pdo->prepare($sql);
        $update->execute($params);

        $_SESSION['auth_user']['full_name'] = $fullName;
        $_SESSION['auth_user']['email'] = $loginId;

        return ['ok' => true, 'message' => 'Login credentials updated successfully.'];
    }

    public function logout(): void
    {
        unset($_SESSION['auth_user']);
    }

    public function user(): ?array
    {
        $user = $_SESSION['auth_user'] ?? null;
        return is_array($user) ? $user : null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    private function validateManagedUserPayload(array $input, bool $requirePassword, ?int $ignoreUserId = null): array
    {
        $pdo = $this->database->connection();
        if (!$pdo instanceof PDO) {
            return [];
        }

        $fullName = trim((string) ($input['full_name'] ?? ''));
        $loginId = strtolower(trim((string) ($input['email'] ?? '')));
        $role = trim((string) ($input['role'] ?? 'user'));
        $password = (string) ($input['password'] ?? '');
        $isActive = in_array((string) ($input['is_active'] ?? '0'), ['1', 'true', 'on', 'yes'], true);
        $errors = [];

        if (mb_strlen($fullName) < 2) {
            $errors['full_name'] = 'Full name must be at least 2 characters.';
        }
        if (mb_strlen($loginId) < 3) {
            $errors['email'] = 'Login ID must be at least 3 characters.';
        }
        if (!in_array($role, ['admin', 'user', 'viewer'], true)) {
            $errors['role'] = 'Invalid role selected.';
        }
        if ($requirePassword && mb_strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        if (!$requirePassword && $password !== '' && mb_strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        $query = 'SELECT id FROM users WHERE email = :email';
        $params = ['email' => $loginId];
        if ($ignoreUserId !== null) {
            $query .= ' AND id <> :id';
            $params['id'] = $ignoreUserId;
        }
        $query .= ' LIMIT 1';
        $duplicate = $pdo->prepare($query);
        $duplicate->execute($params);
        if ($duplicate->fetchColumn()) {
            $errors['email'] = 'Login ID is already used.';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
        }

        return [
            'full_name' => $fullName,
            'email' => $loginId,
            'role' => $role,
            'password' => $password,
            'is_active' => $isActive,
        ];
    }

    private function usersTableExists(PDO $pdo): bool
    {
        $statement = $pdo->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = 'users')");
        $statement->execute();
        return (bool) $statement->fetchColumn();
    }
}
