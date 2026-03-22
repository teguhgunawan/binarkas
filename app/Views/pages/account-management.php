<?php
$flash = $flash ?? null;
$formData = $formData ?? ['name' => '', 'account_name' => '', 'type' => 'Bank', 'reference_number' => '', 'icon' => '', 'description' => '', 'is_active' => '1', 'balance' => '0'];
$formErrors = $formErrors ?? [];
$accounts = $accounts ?? [];
?>

<?php if (!empty($flash)): ?><div class="alert <?= !empty($flash['ok']) ? 'alert-success' : 'alert-danger' ?> border-0 rounded-4 mb-4" role="alert"><?= htmlspecialchars($flash['message'] ?? 'Completed.') ?></div><?php endif; ?>

<section class="surface-card mb-4">
    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            <div class="eyebrow text-success">Account Setup</div>
            <h3 class="h4 mb-2">Manajemen Akun</h3>
            <p class="text-muted mb-0">Struktur akun dan saldo awal sekarang disatukan supaya pengelolaan master account lebih cepat.</p>
        </div>
        <div class="col-xl-4">
            <div class="setup-stat-chip w-100">
                <span class="setup-stat-label">Active Accounts</span>
                <strong><?= count(array_filter($accounts, static fn (array $account): bool => !empty($account['is_active']))) ?></strong>
            </div>
        </div>
    </div>
</section>

<section class="surface-card mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="h5 mb-1">Tambah Account</h3>
            <p class="text-muted mb-0">Buat akun baru beserta saldo awalnya langsung dari satu form.</p>
        </div>
    </div>
    <form method="post" action="<?= htmlspecialchars(app_base('?page=account-management')) ?>">
        <input type="hidden" name="action" value="create-account">
        <input type="hidden" name="current_page" value="account-management">
        <div class="row g-3">
            <div class="col-md-2"><label class="form-label">Account Label</label><input type="text" name="name" class="form-control rounded-4 <?= isset($formErrors['name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $formData['name']) ?>"><?php if (isset($formErrors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($formErrors['name']) ?></div><?php endif; ?></div>
            <div class="col-md-3"><label class="form-label">Account Name</label><input type="text" name="account_name" class="form-control rounded-4 <?= isset($formErrors['account_name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $formData['account_name']) ?>"><?php if (isset($formErrors['account_name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($formErrors['account_name']) ?></div><?php endif; ?></div>
            <div class="col-md-2"><label class="form-label">Type</label><select name="type" class="form-select rounded-4 <?= isset($formErrors['type']) ? 'is-invalid' : '' ?>"><?php foreach (account_type_options() as $option): ?><option value="<?= htmlspecialchars($option) ?>" <?= ($formData['type'] ?? 'Bank') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option><?php endforeach; ?></select><?php if (isset($formErrors['type'])): ?><div class="invalid-feedback"><?= htmlspecialchars($formErrors['type']) ?></div><?php endif; ?></div>
            <div class="col-md-2"><label class="form-label">No. Rek / HP</label><input type="text" name="reference_number" class="form-control rounded-4" value="<?= htmlspecialchars((string) $formData['reference_number']) ?>"></div>
            <div class="col-md-1"><label class="form-label">Icon</label><input type="text" name="icon" class="form-control rounded-4 text-center" value="<?= htmlspecialchars((string) $formData['icon']) ?>"></div>
            <div class="col-md-2"><label class="form-label">Saldo Awal</label><input type="text" name="balance" class="form-control rounded-4 <?= isset($formErrors['balance']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $formData['balance']) ?>"><?php if (isset($formErrors['balance'])): ?><div class="invalid-feedback"><?= htmlspecialchars($formErrors['balance']) ?></div><?php endif; ?></div>
            <div class="col-md-4"><label class="form-label">Keterangan</label><input type="text" name="description" class="form-control rounded-4" value="<?= htmlspecialchars((string) ($formData['description'] ?? '')) ?>"></div>
            <div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= ($formData['is_active'] ?? '1') === '1' ? 'checked' : '' ?>><label class="form-check-label">Active</label></div></div>
        </div>
        <div class="mt-4"><button type="submit" class="btn btn-primary rounded-pill px-4">Create Account</button></div>
    </form>
</section>

<section class="setup-sheet">
    <div class="table-responsive">
        <table class="table table-sm align-middle setup-grid setup-table-accounts mb-0">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th class="setup-col-icon">Icon</th>
                    <th class="setup-col-active">Active?</th>
                    <th>No. Rekening / HP</th>
                    <th>Saldo Awal</th>
                    <th>Keterangan</th>
                    <th class="setup-col-actions">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <form method="post" action="<?= htmlspecialchars(app_base('?page=account-management')) ?>">
                            <input type="hidden" name="action" value="update-account">
                            <input type="hidden" name="current_page" value="account-management">
                            <input type="hidden" name="account_id" value="<?= htmlspecialchars((string) $account['id']) ?>">
                            <td><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $account['name']) ?>"></td>
                            <td><input type="text" name="account_name" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($account['account_name'] ?? $account['name'])) ?>"></td>
                            <td><select name="type" class="form-select form-select-sm"><?php foreach (account_type_options() as $option): ?><option value="<?= htmlspecialchars($option) ?>" <?= ($account['type'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option><?php endforeach; ?></select></td>
                            <td><input type="text" name="icon" class="form-control form-control-sm text-center" value="<?= htmlspecialchars((string) ($account['icon'] ?? '')) ?>"></td>
                            <td class="text-center"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= !empty($account['is_active']) ? 'checked' : '' ?>></td>
                            <td><input type="text" name="reference_number" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($account['reference_number'] ?? '')) ?>"></td>
                            <td><input type="text" name="balance" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $account['balance']) ?>"></td>
                            <td><input type="text" name="description" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($account['description'] ?? '')) ?>"></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill flex-fill">Save</button>
                        </form>
                                    <form method="post" action="<?= htmlspecialchars(app_base('?page=account-management')) ?>" class="flex-fill">
                                        <input type="hidden" name="action" value="delete-account">
                                        <input type="hidden" name="current_page" value="account-management">
                                        <input type="hidden" name="account_id" value="<?= htmlspecialchars((string) $account['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill w-100" onclick="return confirm('Delete this account?');">Delete</button>
                                    </form>
                                </div>
                            </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
