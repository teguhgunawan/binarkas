<?php
$flash = $flash ?? null;
$users = $users ?? [];
$userFormData = $userFormData ?? ['full_name' => '', 'email' => '', 'role' => 'user', 'password' => '', 'is_active' => '1'];
$userFormErrors = $userFormErrors ?? [];
$authUser = $authUser ?? null;
?>

<?php if (!empty($flash)): ?><div class="alert <?= !empty($flash['ok']) ? 'alert-success' : 'alert-danger' ?> border-0 rounded-4 mb-4" role="alert"><?= htmlspecialchars($flash['message'] ?? 'Completed.') ?></div><?php endif; ?>

<section class="surface-card mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="eyebrow text-success">Access Control</div>
            <h3 class="h4 mb-1">Manajemen User</h3>
            <p class="text-muted mb-0">Kelola login ID, role, status aktif, dan reset password dari satu modul.</p>
        </div>
    </div>
    <form method="post" action="<?= htmlspecialchars(app_base('?page=user-management')) ?>">
        <input type="hidden" name="action" value="create-user">
        <input type="hidden" name="current_page" value="user-management">
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control rounded-4 <?= isset($userFormErrors['full_name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $userFormData['full_name']) ?>"><?php if (isset($userFormErrors['full_name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($userFormErrors['full_name']) ?></div><?php endif; ?></div>
            <div class="col-md-3"><label class="form-label">Login ID</label><input type="text" name="email" class="form-control rounded-4 <?= isset($userFormErrors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $userFormData['email']) ?>"><?php if (isset($userFormErrors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars($userFormErrors['email']) ?></div><?php endif; ?></div>
            <div class="col-md-2"><label class="form-label">Role</label><select name="role" class="form-select rounded-4 <?= isset($userFormErrors['role']) ? 'is-invalid' : '' ?>"><option value="admin" <?= ($userFormData['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>Admin</option><option value="user" <?= ($userFormData['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option><option value="viewer" <?= ($userFormData['role'] ?? 'user') === 'viewer' ? 'selected' : '' ?>>Viewer</option></select><?php if (isset($userFormErrors['role'])): ?><div class="invalid-feedback"><?= htmlspecialchars($userFormErrors['role']) ?></div><?php endif; ?></div>
            <div class="col-md-2"><label class="form-label">Password</label><input type="password" name="password" class="form-control rounded-4 <?= isset($userFormErrors['password']) ? 'is-invalid' : '' ?>"><?php if (isset($userFormErrors['password'])): ?><div class="invalid-feedback"><?= htmlspecialchars($userFormErrors['password']) ?></div><?php endif; ?></div>
            <div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= ($userFormData['is_active'] ?? '1') === '1' ? 'checked' : '' ?>><label class="form-check-label">Active</label></div></div>
        </div>
        <div class="mt-4"><button type="submit" class="btn btn-primary rounded-pill px-4">Create User</button></div>
    </form>
</section>

<section class="surface-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Login ID</th>
                    <th>Role</th>
                    <th>Active</th>
                    <th>Reset Password</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <form method="post" action="<?= htmlspecialchars(app_base('?page=user-management')) ?>">
                            <input type="hidden" name="action" value="update-user">
                            <input type="hidden" name="current_page" value="user-management">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $user['id']) ?>">
                            <td><input type="text" name="full_name" class="form-control rounded-4" value="<?= htmlspecialchars((string) $user['full_name']) ?>"></td>
                            <td><input type="text" name="email" class="form-control rounded-4" value="<?= htmlspecialchars((string) $user['email']) ?>"></td>
                            <td><select name="role" class="form-select rounded-4"><option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option><option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option><option value="viewer" <?= ($user['role'] ?? '') === 'viewer' ? 'selected' : '' ?>>Viewer</option></select></td>
                            <td class="text-center"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= !empty($user['is_active']) ? 'checked' : '' ?>></td>
                            <td><input type="password" name="password" class="form-control rounded-4" placeholder="Kosongkan jika tidak ganti"></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-outline-primary rounded-pill flex-fill">Save</button>
                        </form>
                                    <?php if (($authUser['id'] ?? null) !== (int) $user['id']): ?>
                                        <form method="post" action="<?= htmlspecialchars(app_base('?page=user-management')) ?>" class="flex-fill">
                                            <input type="hidden" name="action" value="delete-user">
                                            <input type="hidden" name="current_page" value="user-management">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $user['id']) ?>">
                                            <button type="submit" class="btn btn-outline-danger rounded-pill w-100" onclick="return confirm('Delete this user?');">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary rounded-pill flex-fill" disabled>Current</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
