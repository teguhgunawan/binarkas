<?php
$budgetFormData = $budgetFormData ?? ['name' => '', 'allocated' => '0', 'used' => '0'];
$budgetFormErrors = $budgetFormErrors ?? [];
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
            <h3 class="h5 mb-1">Add Budget</h3>
            <p class="text-muted mb-0">Kelola alokasi dan realisasi budget langsung di PostgreSQL.</p>
        </div>
    </div>
    <form method="post" action="<?= htmlspecialchars(app_base('?page=budgets')) ?>">
        <input type="hidden" name="action" value="create">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Budget Name</label>
                <input type="text" name="name" class="form-control rounded-4 <?= isset($budgetFormErrors['name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $budgetFormData['name']) ?>">
                <?php if (isset($budgetFormErrors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($budgetFormErrors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Allocated</label>
                <input type="text" name="allocated" class="form-control rounded-4 <?= isset($budgetFormErrors['allocated']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $budgetFormData['allocated']) ?>">
                <?php if (isset($budgetFormErrors['allocated'])): ?><div class="invalid-feedback"><?= htmlspecialchars($budgetFormErrors['allocated']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Used</label>
                <input type="text" name="used" class="form-control rounded-4 <?= isset($budgetFormErrors['used']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $budgetFormData['used']) ?>">
                <?php if (isset($budgetFormErrors['used'])): ?><div class="invalid-feedback"><?= htmlspecialchars($budgetFormErrors['used']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Create Budget</button>
        </div>
    </form>
</section>

<section class="surface-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h3 class="h5 mb-0">Monthly Budget</h3>
        <div class="text-muted small">Manage allocation and usage</div>
    </div>
    <div class="vstack gap-3">
        <?php foreach ($budgets as $budget): ?>
            <?php $usedPercent = (float) $budget['allocated'] > 0 ? (int) round(((float) $budget['used'] / (float) $budget['allocated']) * 100) : 0; ?>
            <div class="rounded-4 bg-light-subtle p-3">
                <form method="post" action="<?= htmlspecialchars(app_base('?page=budgets')) ?>" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="budget_id" value="<?= htmlspecialchars((string) $budget['id']) ?>">
                    <div class="col-lg-4">
                        <label class="form-label small">Name</label>
                        <input type="text" name="name" class="form-control rounded-4" value="<?= htmlspecialchars($budget['name']) ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label small">Allocated</label>
                        <input type="text" name="allocated" class="form-control rounded-4" value="<?= htmlspecialchars((string) $budget['allocated']) ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label small">Used</label>
                        <input type="text" name="used" class="form-control rounded-4" value="<?= htmlspecialchars((string) $budget['used']) ?>">
                    </div>
                    <div class="col-lg-2 d-grid">
                        <button type="submit" class="btn btn-outline-primary rounded-pill">Save</button>
                    </div>
                </form>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-3">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="fw-semibold"><?= htmlspecialchars($budget['name']) ?></div>
                            <div class="fw-semibold"><?= $usedPercent ?>%</div>
                        </div>
                        <div class="progress" role="progressbar" aria-valuenow="<?= $usedPercent ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar <?= $usedPercent >= 90 ? 'bg-danger' : ($usedPercent >= 75 ? 'bg-warning' : '') ?>" style="width: <?= $usedPercent ?>%"></div>
                        </div>
                        <div class="text-muted small mt-2"><?= htmlspecialchars(format_idr((float) $budget['used'])) ?> / <?= htmlspecialchars(format_idr((float) $budget['allocated'])) ?></div>
                    </div>
                    <form method="post" action="<?= htmlspecialchars(app_base('?page=budgets')) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="budget_id" value="<?= htmlspecialchars((string) $budget['id']) ?>">
                        <button type="submit" class="btn btn-outline-danger rounded-pill" onclick="return confirm('Delete this budget?');">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
