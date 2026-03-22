<?php
$debtFormData = $debtFormData ?? ['name' => '', 'due' => '', 'amount' => '0', 'status' => 'safe'];
$debtFormErrors = $debtFormErrors ?? [];
$flash = $flash ?? null;
?>

<?php if (!empty($flash)): ?>
    <div class="alert <?= !empty($flash['ok']) ? 'alert-success' : 'alert-danger' ?> border-0 rounded-4 mb-4" role="alert">
        <?= htmlspecialchars($flash['message'] ?? 'Completed.') ?>
    </div>
<?php endif; ?>

<section class="surface-card mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="h5 mb-1">Add Debt</h3>
            <p class="text-muted mb-0">Kelola hutang, due label, nominal, dan status langsung di PostgreSQL.</p>
        </div>
    </div>
    <form method="post" action="<?= htmlspecialchars(app_base('?page=debts')) ?>">
        <input type="hidden" name="action" value="create">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Debt Name</label>
                <input type="text" name="name" class="form-control rounded-4 <?= isset($debtFormErrors['name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $debtFormData['name']) ?>">
                <?php if (isset($debtFormErrors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Due Label</label>
                <input type="text" name="due" class="form-control rounded-4 <?= isset($debtFormErrors['due']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $debtFormData['due']) ?>">
                <?php if (isset($debtFormErrors['due'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['due']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount</label>
                <input type="text" name="amount" class="form-control rounded-4 <?= isset($debtFormErrors['amount']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $debtFormData['amount']) ?>">
                <?php if (isset($debtFormErrors['amount'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['amount']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select rounded-4 <?= isset($debtFormErrors['status']) ? 'is-invalid' : '' ?>">
                    <?php foreach (debt_status_options() as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>" <?= ($debtFormData['status'] ?? 'safe') === $option ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($option)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($debtFormErrors['status'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['status']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Create Debt</button>
        </div>
    </form>
</section>

<section class="surface-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h3 class="h5 mb-0">Debt Register</h3>
        <div class="text-muted small">Active debt records</div>
    </div>
    <div class="vstack gap-3">
        <?php foreach ($debts as $debt): ?>
            <div class="rounded-4 bg-light-subtle p-3">
                <form method="post" action="<?= htmlspecialchars(app_base('?page=debts')) ?>" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="debt_id" value="<?= htmlspecialchars((string) $debt['id']) ?>">
                    <div class="col-lg-4">
                        <label class="form-label small">Name</label>
                        <input type="text" name="name" class="form-control rounded-4" value="<?= htmlspecialchars($debt['name']) ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label small">Due</label>
                        <input type="text" name="due" class="form-control rounded-4" value="<?= htmlspecialchars($debt['due']) ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label small">Amount</label>
                        <input type="text" name="amount" class="form-control rounded-4" value="<?= htmlspecialchars((string) $debt['amount']) ?>">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select rounded-4">
                            <?php foreach (debt_status_options() as $option): ?>
                                <option value="<?= htmlspecialchars($option) ?>" <?= $debt['status'] === $option ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-<?= htmlspecialchars(debt_badge_class($debt['status'])) ?>"><?= htmlspecialchars(ucfirst($debt['status'])) ?></span>
                            <span class="fw-semibold"><?= htmlspecialchars(format_idr((float) $debt['amount'])) ?></span>
                        </div>
                        <button type="submit" class="btn btn-outline-primary rounded-pill">Save</button>
                    </div>
                </form>
                <div class="pt-3">
                    <form method="post" action="<?= htmlspecialchars(app_base('?page=debts')) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="debt_id" value="<?= htmlspecialchars((string) $debt['id']) ?>">
                        <button type="submit" class="btn btn-outline-danger rounded-pill" onclick="return confirm('Delete this debt?');">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
