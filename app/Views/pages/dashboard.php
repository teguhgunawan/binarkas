<section class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="metric-card h-100">
            <span class="metric-label">Net Worth</span>
            <h3><?= htmlspecialchars(format_idr($summary['netWorth'])) ?></h3>
            <p class="metric-meta mb-0">Snapshot total kekayaan aktif</p>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card h-100">
            <span class="metric-label">Income</span>
            <h3><?= htmlspecialchars(format_idr($summary['monthlyIncome'])) ?></h3>
            <p class="metric-meta mb-0">Pendapatan bulan berjalan</p>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card h-100">
            <span class="metric-label">Expense</span>
            <h3><?= htmlspecialchars(format_idr($summary['monthlyExpense'])) ?></h3>
            <p class="metric-meta mb-0">Pengeluaran bulan berjalan</p>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="metric-card h-100">
            <span class="metric-label">Budget Used</span>
            <h3><?= (int) $summary['budgetUsedPercent'] ?>%</h3>
            <div class="progress mt-3" role="progressbar" aria-valuenow="<?= (int) $summary['budgetUsedPercent'] ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?= (int) $summary['budgetUsedPercent'] ?>%"></div>
            </div>
        </div>
    </div>
</section>

<section class="row g-4">
    <div class="col-xl-7">
        <div class="surface-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="h5 mb-0">Recent Transactions</h3>
                <a href="<?= htmlspecialchars(app_base('?page=transactions')) ?>" class="btn btn-sm btn-outline-secondary rounded-pill">See all</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($transactions as $item): ?>
                    <div class="list-group-item px-0 py-3 bg-transparent border-secondary-subtle">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($item['title']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($item['category']) ?> · <?= htmlspecialchars($item['date']) ?></div>
                            </div>
                            <div class="text-end">
                                <span class="badge text-bg-<?= htmlspecialchars(transaction_badge_class($item['type'])) ?> text-uppercase mb-2"><?= htmlspecialchars($item['type']) ?></span>
                                <div class="fw-semibold"><?= htmlspecialchars(format_idr($item['amount'])) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="surface-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="h5 mb-0">Debt Due</h3>
                <span class="badge rounded-pill text-bg-light"><?= count($debts) ?> items</span>
            </div>
            <div class="vstack gap-3 mb-4">
                <?php foreach ($debts as $debt): ?>
                    <div class="rounded-4 debt-item p-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($debt['name']) ?></div>
                                <div class="text-muted small">Jatuh tempo <?= htmlspecialchars($debt['due']) ?></div>
                            </div>
                            <span class="badge text-bg-<?= htmlspecialchars(debt_badge_class($debt['status'])) ?>"><?= htmlspecialchars(ucfirst($debt['status'])) ?></span>
                        </div>
                        <div class="fw-semibold mt-3"><?= htmlspecialchars(format_idr($debt['amount'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <h3 class="h6 mb-3">Account Snapshot</h3>
            <div class="vstack gap-2">
                <?php foreach ($accounts as $account): ?>
                    <div class="d-flex justify-content-between align-items-center rounded-4 bg-light-subtle px-3 py-2">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($account['name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($account['type']) ?></div>
                        </div>
                        <div class="fw-semibold"><?= htmlspecialchars(format_idr($account['balance'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
