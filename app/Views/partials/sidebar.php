<?php
$database = $database ?? ['connected' => false, 'schemaReady' => false, 'error' => null];
$dbClass = 'text-bg-danger';
$dbLabel = 'DB Disconnected';
if (!empty($database['connected']) && !empty($database['schemaReady'])) {
    $dbClass = 'text-bg-success';
    $dbLabel = 'DB Ready';
} elseif (!empty($database['connected'])) {
    $dbClass = 'text-bg-warning text-dark';
    $dbLabel = 'DB Setup Needed';
}
?>
<aside id="appSidebar" class="sidebar-panel d-flex flex-column justify-content-between p-4">
    <div>
        <div class="d-flex align-items-start justify-content-between mb-4">
            <div class="brand-mark">
                <span class="brand-kicker">Personal Wealth OS</span>
                <h1 class="h3 mb-1"><?= htmlspecialchars($config['name'] ?? 'BINARKAS') ?></h1>
                <p class="text-white-50 mb-0">Bootstrap rebuild</p>
            </div>
            <button type="button" id="sidebarCloseBtn" class="btn btn-sm btn-outline-light d-lg-none rounded-circle" aria-label="Close Sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
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
        <div class="text-white-50 small mb-2"><?= htmlspecialchars($authUser['email'] ?? '-') ?></div>
        <span class="badge rounded-pill <?= htmlspecialchars($dbClass) ?> px-3 py-2 mb-2 w-100 text-start"><?= htmlspecialchars($dbLabel) ?></span>
        <?php if (!empty($database['connected']) && empty($database['schemaReady'])): ?>
            <a href="<?= htmlspecialchars(app_base('?page=setup')) ?>" class="btn btn-sm btn-outline-light w-100 rounded-3 mb-2">Run Setup</a>
        <?php endif; ?>
        <?php if (!empty($authUser)): ?>
            <a href="<?= htmlspecialchars(app_base('?page=logout')) ?>" class="btn btn-sm btn-light w-100 rounded-3">Logout</a>
        <?php endif; ?>
    </div>
</aside>
