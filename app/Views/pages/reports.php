<section class="row g-4 mb-4">
    <?php foreach (($reportData['kpis'] ?? []) as $kpi): ?>
        <div class="col-md-6 col-xl-3">
            <div class="metric-card h-100">
                <span class="metric-label"><?= htmlspecialchars($kpi['label']) ?></span>
                <h3><?= is_numeric($kpi['value']) ? htmlspecialchars(format_idr((float) $kpi['value'])) : htmlspecialchars((string) $kpi['value']) ?></h3>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section class="row g-4">
    <div class="col-xl-6">
        <div class="surface-card h-100">
            <h3 class="h5 mb-3">Top Categories</h3>
            <div class="vstack gap-3">
                <?php foreach (($reportData['topCategories'] ?? []) as $category => $total): ?>
                    <div class="d-flex justify-content-between align-items-center rounded-4 bg-light-subtle px-3 py-2">
                        <span class="fw-semibold"><?= htmlspecialchars((string) $category) ?></span>
                        <span><?= htmlspecialchars(format_idr((float) $total)) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="surface-card h-100">
            <h3 class="h5 mb-3">Account Balances</h3>
            <div class="vstack gap-3">
                <?php foreach (($reportData['accountBalances'] ?? []) as $account): ?>
                    <div class="d-flex justify-content-between align-items-center rounded-4 bg-light-subtle px-3 py-2">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($account['name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($account['type']) ?></div>
                        </div>
                        <span><?= htmlspecialchars(format_idr((float) $account['balance'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="surface-card h-100">
            <h3 class="h5 mb-3">Recent Transactions</h3>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Date</th><th>Title</th><th>Category</th><th>Type</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach (($reportData['recentTransactions'] ?? []) as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['date']) ?></td>
                            <td><?= htmlspecialchars($item['title']) ?></td>
                            <td><?= htmlspecialchars($item['category']) ?></td>
                            <td><span class="badge text-bg-<?= htmlspecialchars(transaction_badge_class($item['type'])) ?>"><?= htmlspecialchars(ucfirst($item['type'])) ?></span></td>
                            <td class="text-end"><?= htmlspecialchars(format_idr((float) $item['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="surface-card h-100">
            <h3 class="h5 mb-3">Budget Health</h3>
            <div class="vstack gap-3 mb-4">
                <?php foreach (($reportData['budgets'] ?? []) as $budget): ?>
                    <?php $usedPercent = (float) $budget['allocated'] > 0 ? (int) round(((float) $budget['used'] / (float) $budget['allocated']) * 100) : 0; ?>
                    <div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-semibold"><?= htmlspecialchars($budget['name']) ?></span>
                            <span><?= $usedPercent ?>%</span>
                        </div>
                        <div class="progress"><div class="progress-bar <?= $usedPercent >= 90 ? 'bg-danger' : ($usedPercent >= 75 ? 'bg-warning' : '') ?>" style="width: <?= $usedPercent ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <h4 class="h6 mb-3">Debt Alerts</h4>
            <div class="vstack gap-2">
                <?php foreach (($reportData['debts'] ?? []) as $debt): ?>
                    <div class="d-flex justify-content-between align-items-center rounded-4 bg-light-subtle px-3 py-2">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($debt['name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($debt['due']) ?></div>
                        </div>
                        <span class="badge text-bg-<?= htmlspecialchars(debt_badge_class($debt['status'])) ?>"><?= htmlspecialchars(ucfirst($debt['status'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
