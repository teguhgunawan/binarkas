<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($config['name'] ?? 'BINARKAS') . ' - Login') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= htmlspecialchars(app_base('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <div class="mx-auto" style="max-width: 560px;">
        <div class="surface-card">
            <div class="eyebrow mb-2">Secure Access</div>
            <h1 class="h3 mb-2">Login to BINARKAS</h1>
            <p class="text-muted mb-4">Gunakan akun administrator untuk mengakses dashboard dan modul finansial.</p>

            <div class="alert alert-info border-0 rounded-4" role="alert">
                <strong>Default initial login</strong><br>
                Email: <?= htmlspecialchars(config('app.auth.admin_email')) ?><br>
                Password: <?= htmlspecialchars(config('app.auth.admin_password')) ?><br>
                Kredensial ini bisa diubah dari menu <strong>Settings</strong> setelah login.
            </div>

            <?php if (!empty($flash)): ?>
                <div class="alert alert-danger border-0 rounded-4" role="alert">
                    <?= htmlspecialchars($flash['message'] ?? 'Login failed.') ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars(app_base('?page=login')) ?>" class="vstack gap-3">
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control rounded-4" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control rounded-4" required>
                </div>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Login</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
