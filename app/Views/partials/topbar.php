<header class="topbar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="eyebrow">Overview</div>
        <h2 class="h3 mb-1"><?= htmlspecialchars(page_title($page)) ?></h2>
        <p class="text-muted mb-0">BINARKAS modular PHP baseline with Bootstrap and PDO-ready architecture.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <?php if (!empty($database['connected']) && !empty($database['schemaReady'])): ?>
            <span class="badge rounded-pill text-bg-success px-3 py-2">Database Ready</span>
        <?php elseif (!empty($database['connected'])): ?>
            <span class="badge rounded-pill text-bg-warning text-dark px-3 py-2">Database Empty / Setup Required</span>
            <a href="<?= htmlspecialchars(app_base('?page=setup')) ?>" class="btn btn-outline-secondary rounded-pill px-3">Open Setup</a>
        <?php else: ?>
            <span class="badge rounded-pill text-bg-danger px-3 py-2">Database Disconnected</span>
        <?php endif; ?>
        <?php if (!empty($authUser)): ?>
            <span class="badge rounded-pill text-bg-light px-3 py-2"><?= htmlspecialchars($authUser['email']) ?></span>
            <a href="<?= htmlspecialchars(app_base('?page=logout')) ?>" class="btn btn-outline-secondary rounded-pill px-3">Logout</a>
        <?php endif; ?>
        <button class="btn btn-light rounded-pill px-3">Search</button>
        <button class="btn btn-primary rounded-pill px-4">Quick Add</button>
    </div>
</header>
<?php if (empty($database['connected']) && !empty($database['error'])): ?>
    <div class="alert alert-warning border-0 rounded-4 mb-4" role="alert">
        <strong>Database belum aktif.</strong>
        App sedang memakai fallback dataset lokal. Detail koneksi terakhir: <?= htmlspecialchars($database['error']) ?>
    </div>
<?php elseif (!empty($database['connected']) && empty($database['schemaReady'])): ?>
    <div class="alert alert-info border-0 rounded-4 mb-4" role="alert">
        <strong>Database terhubung, tetapi schema aplikasi belum siap.</strong>
        Jalankan setup dari <a href="<?= htmlspecialchars(app_base('?page=setup')) ?>" class="alert-link">halaman Database Setup</a> untuk membuat tabel awal atau menjalankan migration yang tertunda.
    </div>
<?php endif; ?>
