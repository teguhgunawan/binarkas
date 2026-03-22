<?php
$transactionFormData = $transactionFormData ?? ['date' => date('Y-m-d'), 'title' => '', 'category' => '', 'type' => 'expense', 'amount' => '0'];
$transactionFormErrors = $transactionFormErrors ?? [];
$flash = $flash ?? null;
$categories = array_values(array_filter($categories ?? [], static fn (array $category): bool => !empty($category['is_active'])));
?>

<?php if (!empty($flash)): ?><div class="alert <?= !empty($flash['ok']) ? 'alert-success' : 'alert-danger' ?> border-0 rounded-4 mb-4" role="alert"><?= htmlspecialchars($flash['message'] ?? 'Completed.') ?></div><?php endif; ?>

<section class="surface-card mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h3 class="h5 mb-1">Add Transaction</h3><p class="text-muted mb-0">Simpan income, expense, atau transfer langsung ke PostgreSQL.</p></div></div>
    <form method="post" action="<?= htmlspecialchars(app_base('?page=transactions')) ?>">
        <input type="hidden" name="action" value="create">
        <div class="row g-3">
            <div class="col-md-2"><label class="form-label">Date</label><input type="date" name="date" class="form-control rounded-4 <?= isset($transactionFormErrors['date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $transactionFormData['date']) ?>"><?php if (isset($transactionFormErrors['date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($transactionFormErrors['date']) ?></div><?php endif; ?></div>
            <div class="col-md-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control rounded-4 <?= isset($transactionFormErrors['title']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $transactionFormData['title']) ?>"><?php if (isset($transactionFormErrors['title'])): ?><div class="invalid-feedback"><?= htmlspecialchars($transactionFormErrors['title']) ?></div><?php endif; ?></div>
            <div class="col-md-2"><label class="form-label">Type</label><select name="type" data-category-filter class="form-select rounded-4 <?= isset($transactionFormErrors['type']) ? 'is-invalid' : '' ?>"><?php foreach (transaction_type_options() as $option): ?><option value="<?= htmlspecialchars($option) ?>" <?= ($transactionFormData['type'] ?? 'expense') === $option ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($option)) ?></option><?php endforeach; ?></select><?php if (isset($transactionFormErrors['type'])): ?><div class="invalid-feedback"><?= htmlspecialchars($transactionFormErrors['type']) ?></div><?php endif; ?></div>
            <div class="col-md-3"><label class="form-label">Category</label><select name="category" data-category-target class="form-select rounded-4 <?= isset($transactionFormErrors['category']) ? 'is-invalid' : '' ?>"><?php foreach ($categories as $category): ?><option value="<?= htmlspecialchars($category['name']) ?>" data-type="<?= htmlspecialchars($category['type']) ?>" <?= ($transactionFormData['category'] ?? '') === $category['name'] ? 'selected' : '' ?>><?= htmlspecialchars(trim(($category['icon'] ?? '') . ' ' . $category['name'])) ?></option><?php endforeach; ?></select><?php if (isset($transactionFormErrors['category'])): ?><div class="invalid-feedback"><?= htmlspecialchars($transactionFormErrors['category']) ?></div><?php endif; ?></div>
            <div class="col-md-2"><label class="form-label">Amount</label><input type="text" name="amount" class="form-control rounded-4 <?= isset($transactionFormErrors['amount']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $transactionFormData['amount']) ?>"><?php if (isset($transactionFormErrors['amount'])): ?><div class="invalid-feedback"><?= htmlspecialchars($transactionFormErrors['amount']) ?></div><?php endif; ?></div>
        </div>
        <div class="mt-4"><button type="submit" class="btn btn-primary rounded-pill px-4">Create Transaction</button></div>
    </form>
</section>

<section class="surface-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><h3 class="h5 mb-0">Transaction Ledger</h3><div class="text-muted small">Latest 20 records</div></div>
    <div class="vstack gap-3">
        <?php foreach ($transactions as $item): ?>
            <div class="rounded-4 bg-light-subtle p-3">
                <form method="post" action="<?= htmlspecialchars(app_base('?page=transactions')) ?>" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="update"><input type="hidden" name="transaction_id" value="<?= htmlspecialchars((string) $item['id']) ?>">
                    <div class="col-lg-2"><label class="form-label small">Date</label><input type="date" name="date" class="form-control rounded-4" value="<?= htmlspecialchars((string) $item['date']) ?>"></div>
                    <div class="col-lg-2"><label class="form-label small">Title</label><input type="text" name="title" class="form-control rounded-4" value="<?= htmlspecialchars($item['title']) ?>"></div>
                    <div class="col-lg-2"><label class="form-label small">Type</label><select name="type" data-category-filter class="form-select rounded-4"><?php foreach (transaction_type_options() as $option): ?><option value="<?= htmlspecialchars($option) ?>" <?= $item['type'] === $option ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($option)) ?></option><?php endforeach; ?></select></div>
                    <div class="col-lg-3"><label class="form-label small">Category</label><select name="category" data-category-target class="form-select rounded-4"><?php foreach ($categories as $category): ?><option value="<?= htmlspecialchars($category['name']) ?>" data-type="<?= htmlspecialchars($category['type']) ?>" <?= $item['category'] === $category['name'] ? 'selected' : '' ?>><?= htmlspecialchars(trim(($category['icon'] ?? '') . ' ' . $category['name'])) ?></option><?php endforeach; ?></select></div>
                    <div class="col-lg-1"><label class="form-label small">Amount</label><input type="text" name="amount" class="form-control rounded-4" value="<?= htmlspecialchars((string) $item['amount']) ?>"></div>
                    <div class="col-lg-2 d-grid"><button type="submit" class="btn btn-outline-primary rounded-pill">Save</button></div>
                </form>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-3"><div class="d-flex align-items-center gap-2"><span class="badge text-bg-<?= htmlspecialchars(transaction_badge_class($item['type'])) ?>"><?= htmlspecialchars(ucfirst($item['type'])) ?></span><span class="fw-semibold"><?= htmlspecialchars(format_idr((float) $item['amount'])) ?></span></div><form method="post" action="<?= htmlspecialchars(app_base('?page=transactions')) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="transaction_id" value="<?= htmlspecialchars((string) $item['id']) ?>"><button type="submit" class="btn btn-outline-danger rounded-pill" onclick="return confirm('Delete this transaction?');">Delete</button></form></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
