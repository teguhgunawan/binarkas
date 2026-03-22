<?php
$accounts = $accounts ?? [];
$filters = $accountLedgerFilters ?? ['account' => '__all', 'date_from' => date('Y-m-01'), 'date_to' => date('Y-m-d'), 'keyword' => ''];
$rows = $accountLedgerRows ?? [];
$groupedRows = $accountLedgerGroupedRows ?? [];
$selectedAccount = (string) ($filters['account'] ?? '__all');
$isAllAccounts = $selectedAccount === '__all';
?>

<section class="surface-card mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <div class="eyebrow text-success">Account Ledger</div>
            <h3 class="h4 mb-1">Daftar Transaksi Akun</h3>
            <p class="text-muted mb-0">Filter berdasarkan akun, periode aktif, dan kata kunci.</p>
        </div>
        <span class="badge rounded-pill text-bg-primary"><?= htmlspecialchars((string) $selectedAccountLabel) ?></span>
    </div>

    <form method="get" action="<?= htmlspecialchars(app_base()) ?>" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="accounts">
        <div class="col-lg-3">
            <label class="form-label">Daftar Account</label>
            <select name="account" class="form-select rounded-4">
                <option value="__all" <?= $isAllAccounts ? 'selected' : '' ?>>Semua Akun</option>
                <?php foreach ($accounts as $account): ?>
                    <?php $accountName = (string) ($account['name'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($accountName) ?>" <?= $selectedAccount === $accountName ? 'selected' : '' ?>>
                        <?= htmlspecialchars($accountName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2">
            <label class="form-label">Periode Dari</label>
            <input type="date" name="date_from" class="form-control rounded-4" value="<?= htmlspecialchars((string) ($filters['date_from'] ?? date('Y-m-01'))) ?>">
        </div>
        <div class="col-lg-2">
            <label class="form-label">Periode Sampai</label>
            <input type="date" name="date_to" class="form-control rounded-4" value="<?= htmlspecialchars((string) ($filters['date_to'] ?? date('Y-m-d'))) ?>">
        </div>
        <div class="col-lg-3">
            <label class="form-label">Kata Kunci</label>
            <input type="text" name="keyword" class="form-control rounded-4" placeholder="Judul, kategori, nominal, akun" value="<?= htmlspecialchars((string) ($filters['keyword'] ?? '')) ?>">
        </div>
        <div class="col-lg-2 d-grid">
            <button type="submit" class="btn btn-primary rounded-pill">Tampilkan</button>
        </div>
    </form>
</section>

<section class="surface-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h5 mb-0"><?= $isAllAccounts ? 'Transaksi Semua Akun (Grouped Per Hari)' : 'Transaksi Akun' ?></h3>
        <span class="text-muted small"><?= count($rows) ?> transaksi</span>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-light border rounded-4 mb-0">Tidak ada transaksi untuk filter yang dipilih.</div>
    <?php elseif ($isAllAccounts): ?>
        <div class="vstack gap-3">
            <?php foreach ($groupedRows as $date => $dateRows): ?>
                <div class="rounded-4 border p-3">
                    <div class="fw-semibold mb-2"><?= htmlspecialchars((string) $date) ?></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dateRows as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($item['account_name'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string) ($item['title'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string) ($item['category'] ?? '-')) ?></td>
                                        <td><span class="badge text-bg-<?= htmlspecialchars(transaction_badge_class((string) ($item['type'] ?? ''))) ?>"><?= htmlspecialchars(ucfirst((string) ($item['type'] ?? '-'))) ?></span></td>
                                        <td class="text-end fw-semibold"><?= htmlspecialchars(format_idr((float) ($item['amount'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($item['date'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) ($item['title'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string) ($item['category'] ?? '-')) ?></td>
                            <td><span class="badge text-bg-<?= htmlspecialchars(transaction_badge_class((string) ($item['type'] ?? ''))) ?>"><?= htmlspecialchars(ucfirst((string) ($item['type'] ?? '-'))) ?></span></td>
                            <td class="text-end fw-semibold"><?= htmlspecialchars(format_idr((float) ($item['amount'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
