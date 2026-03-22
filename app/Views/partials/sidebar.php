<aside class="sidebar-panel d-flex flex-column justify-content-between p-4">
    <div>
        <div class="brand-mark mb-4">
            <span class="brand-kicker">Personal Wealth OS</span>
            <h1 class="h3 mb-1"><?= htmlspecialchars($config['name'] ?? 'BINARKAS') ?></h1>
            <p class="text-white-50 mb-0">Bootstrap rebuild</p>
        </div>

        <?php
        $coreNav = [
            'dashboard' => 'Dashboard',
            'transactions' => 'Transactions',
            'accounts' => 'Accounts',
            'budgets' => 'Budgets',
            'debts' => 'Debts',
            'reports' => 'Reports',
        ];
        $setupNav = [
            'user-management' => 'Manajemen User',
            'account-management' => 'Manajemen Akun',
            'category-management' => 'Manajemen Kategori',
        ];
        ?>

        <div class="mb-4">
            <div class="sidebar-group-title mb-2">Core Modules</div>
            <nav class="nav flex-column gap-2">
                <?php foreach ($coreNav as $key => $label): ?>
                    <a class="nav-link rounded-4 px-3 py-2 <?= $page === $key ? 'active' : '' ?>" href="<?= htmlspecialchars(app_base('?page=' . $key)) ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div>
            <div class="sidebar-group-title mb-2">Setup</div>
            <nav class="nav flex-column gap-2">
                <?php foreach ($setupNav as $key => $label): ?>
                    <a class="nav-link rounded-4 px-3 py-2 <?= $page === $key ? 'active' : '' ?>" href="<?= htmlspecialchars(app_base('?page=' . $key)) ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    <div class="sidebar-footer rounded-4 p-3">
        <div class="small text-uppercase text-white-50">Signed in as</div>
        <div class="fw-semibold"><?= htmlspecialchars($authUser['full_name'] ?? 'Guest') ?></div>
        <div class="text-white-50 small"><?= htmlspecialchars($authUser['role'] ?? 'guest') ?></div>
    </div>
</aside>
