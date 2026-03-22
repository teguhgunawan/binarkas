<?php
$accounts = $accounts ?? [];
$categories = array_values(array_filter($categories ?? [], static fn (array $category): bool => !empty($category['is_active'])));
$filters = $accountLedgerFilters ?? ['account' => '__all', 'date_from' => date('Y-m-01'), 'date_to' => date('Y-m-d'), 'keyword' => ''];
$rows = $accountLedgerRows ?? [];
$groupedRows = $accountLedgerGroupedRows ?? [];
$flash = $flash ?? null;
$selectedAccount = (string) ($filters['account'] ?? '__all');
$selectedAccountLabel = (string) ($selectedAccountLabel ?? 'Semua Akun');
if (empty($groupedRows) && !empty($rows)) {
    $groupedRows = [];
    foreach ($rows as $row) {
        $day = (string) ($row['date'] ?? 'Unknown Date');
        if (!array_key_exists($day, $groupedRows)) {
            $groupedRows[$day] = [];
        }
        $groupedRows[$day][] = $row;
    }
}
?>

<?php if (!empty($flash)): ?><div class="alert <?= !empty($flash['ok']) ? 'alert-success' : 'alert-danger' ?> border-0 rounded-4 mb-4" role="alert"><?= htmlspecialchars($flash['message'] ?? 'Completed.') ?></div><?php endif; ?>

<section class="surface-card mb-4">
    <form method="get" action="<?= htmlspecialchars(app_base()) ?>" class="accounts-filter-bar">
        <input type="hidden" name="page" value="accounts">
        <input type="hidden" name="account" value="<?= htmlspecialchars($selectedAccount) ?>" data-account-input>
        <div>
            <label class="form-label">Daftar Account</label>
            <button type="button" class="btn btn-outline-secondary w-100 rounded-4 text-start d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#accountPickerModal">
                <span data-account-label><?= htmlspecialchars($selectedAccountLabel) ?></span>
                <span><i class="bi bi-chevron-down"></i></span>
            </button>
        </div>
        <div>
            <label class="form-label">Periode Dari</label>
            <input type="date" name="date_from" class="form-control rounded-4" value="<?= htmlspecialchars((string) ($filters['date_from'] ?? date('Y-m-01'))) ?>">
        </div>
        <div>
            <label class="form-label">Periode Sampai</label>
            <input type="date" name="date_to" class="form-control rounded-4" value="<?= htmlspecialchars((string) ($filters['date_to'] ?? date('Y-m-d'))) ?>">
        </div>
        <div>
            <label class="form-label">Kata Kunci</label>
            <input type="text" name="keyword" class="form-control rounded-4" placeholder="Cari judul, kategori, akun, nominal" value="<?= htmlspecialchars((string) ($filters['keyword'] ?? '')) ?>">
        </div>
        <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="<?= htmlspecialchars(app_base('?page=transactions')) ?>" class="btn btn-success rounded-pill px-3"><i class="bi bi-plus-circle me-1"></i>Tambah Transaksi</a>
    </form>
</section>

<section class="surface-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h5 mb-0">Daftar Transaksi Akun</h3>
        <span class="text-muted small"><?= count($rows) ?> transaksi</span>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-light border rounded-4 mb-0">Tidak ada transaksi untuk filter yang dipilih.</div>
    <?php else: ?>
        <div class="accounts-grid-header">
            <div>Account</div>
            <div>Transaksi</div>
            <div>Kategori</div>
            <div>Tipe</div>
            <div class="text-end">Nominal</div>
            <div class="text-end">Aksi</div>
        </div>
        <div class="accordion mt-2" id="accountsAccordion">
            <?php $groupIndex = 0; ?>
            <?php foreach ($groupedRows as $date => $dateRows): ?>
                <?php $groupIndex++; $collapseId = 'groupDay' . $groupIndex; ?>
                <div class="accordion-item border-0 mb-2 rounded-4 overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $groupIndex > 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($collapseId) ?>">
                            <span class="fw-semibold me-2"><?= htmlspecialchars((string) $date) ?></span>
                            <span class="text-muted small">(<?= count($dateRows) ?> transaksi)</span>
                        </button>
                    </h2>
                    <div id="<?= htmlspecialchars($collapseId) ?>" class="accordion-collapse collapse <?= $groupIndex === 1 ? 'show' : '' ?>" data-bs-parent="#accountsAccordion">
                        <div class="accordion-body p-2">
                            <?php foreach ($dateRows as $item): ?>
                                <div class="accounts-grid-row">
                                    <div><?= htmlspecialchars((string) ($item['account_name'] ?? '-')) ?></div>
                                    <div><?= htmlspecialchars((string) ($item['title'] ?? '-')) ?></div>
                                    <div><?= htmlspecialchars((string) ($item['category'] ?? '-')) ?></div>
                                    <div><span class="badge text-bg-<?= htmlspecialchars(transaction_badge_class((string) ($item['type'] ?? ''))) ?>"><?= htmlspecialchars(ucfirst((string) ($item['type'] ?? '-'))) ?></span></div>
                                    <div class="text-end fw-semibold"><?= htmlspecialchars(format_idr((float) ($item['amount'] ?? 0))) ?></div>
                                    <div class="accounts-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" data-edit-transaction
                                            data-transaction-id="<?= htmlspecialchars((string) ($item['id'] ?? '')) ?>"
                                            data-date="<?= htmlspecialchars((string) ($item['date'] ?? '')) ?>"
                                            data-title="<?= htmlspecialchars((string) ($item['title'] ?? '')) ?>"
                                            data-type="<?= htmlspecialchars((string) ($item['type'] ?? 'expense')) ?>"
                                            data-account-name="<?= htmlspecialchars((string) ($item['account_name'] ?? '')) ?>"
                                            data-category="<?= htmlspecialchars((string) ($item['category'] ?? '')) ?>"
                                            data-amount="<?= htmlspecialchars((string) ($item['amount'] ?? '0')) ?>"
                                            data-filter-account="<?= htmlspecialchars($selectedAccount) ?>"
                                            data-filter-date-from="<?= htmlspecialchars((string) ($filters['date_from'] ?? date('Y-m-01'))) ?>"
                                            data-filter-date-to="<?= htmlspecialchars((string) ($filters['date_to'] ?? date('Y-m-d'))) ?>"
                                            data-filter-keyword="<?= htmlspecialchars((string) ($filters['keyword'] ?? '')) ?>"
                                            title="Edit transaksi">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form method="post" action="<?= htmlspecialchars(app_base('?page=accounts')) ?>" class="d-inline">
                                            <input type="hidden" name="action" value="delete-transaction">
                                            <input type="hidden" name="transaction_id" value="<?= htmlspecialchars((string) ($item['id'] ?? '')) ?>">
                                            <input type="hidden" name="account" value="<?= htmlspecialchars($selectedAccount) ?>">
                                            <input type="hidden" name="date_from" value="<?= htmlspecialchars((string) ($filters['date_from'] ?? date('Y-m-01'))) ?>">
                                            <input type="hidden" name="date_to" value="<?= htmlspecialchars((string) ($filters['date_to'] ?? date('Y-m-d'))) ?>">
                                            <input type="hidden" name="keyword" value="<?= htmlspecialchars((string) ($filters['keyword'] ?? '')) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus transaksi" onclick="return confirm('Hapus transaksi ini?');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="accountPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-account-choice="__all" data-account-label="Semua Akun">
                        <span>Semua Akun</span>
                        <i class="bi bi-collection"></i>
                    </button>
                    <?php foreach ($accounts as $account): ?>
                        <?php $accountName = (string) ($account['name'] ?? ''); ?>
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-account-choice="<?= htmlspecialchars($accountName) ?>" data-account-label="<?= htmlspecialchars($accountName) ?>">
                            <span><?= htmlspecialchars($accountName) ?></span>
                            <small class="text-muted"><?= htmlspecialchars((string) ($account['type'] ?? '')) ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <a href="<?= htmlspecialchars(app_base('?page=account-management')) ?>" class="btn btn-outline-secondary rounded-pill">
                    <i class="bi bi-pencil-square me-1"></i>Edit Akun
                </a>
                <a href="<?= htmlspecialchars(app_base('?page=account-management')) ?>" class="btn btn-primary rounded-pill">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Akun
                </a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="transactionEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= htmlspecialchars(app_base('?page=accounts')) ?>" class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" name="action" value="update-transaction">
                <input type="hidden" id="edit-transaction-id" name="transaction_id" value="">
                <input type="hidden" id="edit-filter-account" name="account" value="<?= htmlspecialchars($selectedAccount) ?>">
                <input type="hidden" id="edit-filter-date-from" name="date_from" value="<?= htmlspecialchars((string) ($filters['date_from'] ?? date('Y-m-01'))) ?>">
                <input type="hidden" id="edit-filter-date-to" name="date_to" value="<?= htmlspecialchars((string) ($filters['date_to'] ?? date('Y-m-d'))) ?>">
                <input type="hidden" id="edit-filter-keyword" name="keyword" value="<?= htmlspecialchars((string) ($filters['keyword'] ?? '')) ?>">
                <div class="col-md-6">
                    <label class="form-label">Tanggal</label>
                    <input type="date" id="edit-transaction-date" name="date" class="form-control rounded-4">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipe</label>
                    <select id="edit-transaction-type" name="type" class="form-select rounded-4" data-category-filter>
                        <?php foreach (transaction_type_options() as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst($option)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Judul</label>
                    <input type="text" id="edit-transaction-title" name="title" class="form-control rounded-4">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account</label>
                    <select id="edit-transaction-account" name="account_name" class="form-select rounded-4">
                        <?php foreach ($accounts as $account): ?>
                            <?php $accountName = (string) ($account['name'] ?? ''); ?>
                            <option value="<?= htmlspecialchars($accountName) ?>"><?= htmlspecialchars($accountName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <select id="edit-transaction-category" name="category" class="form-select rounded-4" data-category-target>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars((string) ($category['name'] ?? '')) ?>" data-type="<?= htmlspecialchars((string) ($category['type'] ?? 'expense')) ?>">
                                <?= htmlspecialchars((string) ($category['name'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Amount</label>
                    <input type="text" id="edit-transaction-amount" name="amount" class="form-control rounded-4">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary rounded-pill">Simpan</button>
            </div>
        </form>
    </div>
</div>
