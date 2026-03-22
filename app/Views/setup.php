<?php
$pending = $status['pendingMigrations'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($config['name'] ?? 'BINARKAS') . ' - Database Setup') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= htmlspecialchars(app_base('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <div class="mx-auto" style="max-width: 920px;">
        <div class="surface-card mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <div class="eyebrow">Installer</div>
                    <h1 class="h3 mb-1">Database Setup</h1>
                    <p class="text-muted mb-0">Inisialisasi database kosong atau jalankan migration baru langsung dari web app.</p>
                </div>
                <a href="<?= htmlspecialchars(app_base('?page=dashboard')) ?>" class="btn btn-outline-secondary rounded-pill">Back to App</a>
            </div>

            <?php if (!empty($flash)): ?>
                <div class="alert <?= !empty($flash['ok']) ? 'alert-success' : 'alert-danger' ?> border-0 rounded-4" role="alert">
                    <?= htmlspecialchars($flash['message'] ?? 'Completed.') ?>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="rounded-4 bg-light-subtle p-3 h-100">
                        <div class="small text-uppercase text-muted mb-2">Connection</div>
                        <div class="fw-semibold"><?= !empty($status['connected']) ? 'Connected' : 'Disconnected' ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded-4 bg-light-subtle p-3 h-100">
                        <div class="small text-uppercase text-muted mb-2">Schema</div>
                        <div class="fw-semibold"><?= !empty($status['schemaReady']) ? 'Ready' : 'Not Ready' ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="rounded-4 bg-light-subtle p-3 h-100">
                        <div class="small text-uppercase text-muted mb-2">Pending</div>
                        <div class="fw-semibold"><?= count($pending) ?> migration(s)</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($status['error'])): ?>
                <div class="alert alert-warning border-0 rounded-4" role="alert">
                    <?= htmlspecialchars($status['error']) ?>
                </div>
            <?php endif; ?>

            <h2 class="h5 mt-4">Migration Queue</h2>
            <ul class="list-group list-group-flush mb-4">
                <?php foreach ($pending as $migration): ?>
                    <li class="list-group-item px-0 py-3 bg-transparent"><?= htmlspecialchars($migration) ?></li>
                <?php endforeach; ?>
                <?php if (empty($pending)): ?>
                    <li class="list-group-item px-0 py-3 bg-transparent text-muted">No pending migrations.</li>
                <?php endif; ?>
            </ul>

            <form method="post" action="<?= htmlspecialchars(app_base('?page=setup')) ?>">
                <input type="hidden" name="action" value="run-migrations">
                <button type="submit" class="btn btn-primary rounded-pill px-4" <?= empty($status['connected']) ? 'disabled' : '' ?>>Run Setup / Migrations</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
