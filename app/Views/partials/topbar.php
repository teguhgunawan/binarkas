<header class="topbar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="sidebarToggleBtn" class="btn btn-light rounded-pill px-3" aria-label="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>
        <?php if ($page !== 'dashboard'): ?>
            <a href="<?= htmlspecialchars(app_base('?page=dashboard')) ?>" class="btn btn-light rounded-circle" title="Kembali ke Dashboard">
                <i class="bi bi-arrow-left"></i>
            </a>
        <?php endif; ?>
        <h2 class="h4 mb-0"><?= htmlspecialchars(page_title($page)) ?></h2>
    </div>
</header>
