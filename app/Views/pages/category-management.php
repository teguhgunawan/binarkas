<?php
$flash = $flash ?? null;
$categoryFormData = $categoryFormData ?? ['name' => '', 'type' => 'expense', 'group_name' => 'expense', 'icon' => '', 'is_active' => '1', 'sort_order' => '10'];
$categoryFormErrors = $categoryFormErrors ?? [];
$groupedCategories = $groupedCategories ?? [];
$categoryGroupsMeta = $categoryGroupsMeta ?? category_setup_groups();
?>

<?php if (!empty($flash)): ?><div class="alert <?= !empty($flash['ok']) ? 'alert-success' : 'alert-danger' ?> border-0 rounded-4 mb-4" role="alert"><?= htmlspecialchars($flash['message'] ?? 'Completed.') ?></div><?php endif; ?>

<section class="mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <div class="eyebrow text-success">Category Master</div>
            <h3 class="h4 mb-1">Manajemen Kategori</h3>
            <p class="text-muted mb-0">Kategori dipisah per grup agar tetap mudah dipetakan dari spreadsheet lama ke aplikasi.</p>
        </div>
        <a href="<?= htmlspecialchars(app_base('?page=user-management')) ?>" class="btn btn-outline-secondary rounded-pill px-4">Kembali ke Manajemen User</a>
    </div>
    <div class="row g-4">
        <?php foreach ($categoryGroupsMeta as $groupKey => $meta): ?>
            <?php $items = $groupedCategories[$groupKey] ?? []; ?>
            <div class="col-12 <?= in_array($groupKey, ['debt_payoff', 'transfer'], true) ? 'col-xl-6' : 'col-xl-3' ?>">
                <div class="setup-sheet h-100">
                    <div class="setup-sheet-header d-flex align-items-baseline justify-content-between gap-3">
                        <div>
                            <h4 class="h5 mb-1"><?= htmlspecialchars($meta['label']) ?> <span class="setup-sheet-hint">(<?= htmlspecialchars($meta['hint']) ?>)</span></h4>
                            <div class="text-muted small">Grouped category management</div>
                        </div>
                        <span class="badge rounded-pill text-bg-light"><?= count($items) ?> item</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle setup-grid <?= htmlspecialchars($meta['table_class']) ?> mb-0">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars($meta['label']) ?> Category</th>
                                    <th class="setup-col-icon">Icon</th>
                                    <th class="setup-col-active">Active?</th>
                                    <th class="setup-col-actions">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="setup-create-row">
                                    <form method="post" action="<?= htmlspecialchars(app_base('?page=category-management')) ?>">
                                        <input type="hidden" name="action" value="create-category">
                                        <input type="hidden" name="current_page" value="category-management">
                                        <input type="hidden" name="group_name" value="<?= htmlspecialchars($groupKey) ?>">
                                        <input type="hidden" name="type" value="<?= htmlspecialchars($meta['default_type']) ?>">
                                        <input type="hidden" name="sort_order" value="<?= htmlspecialchars((string) ($categoryFormData['sort_order'] ?? '10')) ?>">
                                        <td>
                                            <input type="text" name="name" class="form-control form-control-sm <?= ($categoryFormData['group_name'] ?? '') === $groupKey && isset($categoryFormErrors['name']) ? 'is-invalid' : '' ?>" value="<?= ($categoryFormData['group_name'] ?? '') === $groupKey ? htmlspecialchars((string) $categoryFormData['name']) : '' ?>" placeholder="Tambah kategori">
                                            <?php if (($categoryFormData['group_name'] ?? '') === $groupKey && isset($categoryFormErrors['name'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($categoryFormErrors['name']) ?></div><?php endif; ?>
                                        </td>
                                        <td><input type="text" name="icon" class="form-control form-control-sm text-center" value="<?= ($categoryFormData['group_name'] ?? '') === $groupKey ? htmlspecialchars((string) $categoryFormData['icon']) : '' ?>" placeholder="??"></td>
                                        <td class="text-center"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= (($categoryFormData['group_name'] ?? '') === $groupKey ? ($categoryFormData['is_active'] ?? '1') : '1') === '1' ? 'checked' : '' ?>></td>
                                        <td><button type="submit" class="btn btn-sm btn-primary rounded-pill w-100">Add</button></td>
                                    </form>
                                </tr>
                                <?php foreach ($items as $category): ?>
                                    <tr>
                                        <form method="post" action="<?= htmlspecialchars(app_base('?page=category-management')) ?>">
                                            <input type="hidden" name="action" value="update-category">
                                            <input type="hidden" name="current_page" value="category-management">
                                            <input type="hidden" name="category_id" value="<?= htmlspecialchars((string) $category['id']) ?>">
                                            <input type="hidden" name="group_name" value="<?= htmlspecialchars((string) $category['group_name']) ?>">
                                            <input type="hidden" name="type" value="<?= htmlspecialchars((string) $category['type']) ?>">
                                            <input type="hidden" name="sort_order" value="<?= htmlspecialchars((string) ($category['sort_order'] ?? 10)) ?>">
                                            <td><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars((string) $category['name']) ?>"></td>
                                            <td><input type="text" name="icon" class="form-control form-control-sm text-center" value="<?= htmlspecialchars((string) ($category['icon'] ?? '')) ?>"></td>
                                            <td class="text-center"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= !empty($category['is_active']) ? 'checked' : '' ?>></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill flex-fill">Save</button>
                                        </form>
                                                    <form method="post" action="<?= htmlspecialchars(app_base('?page=category-management')) ?>" class="flex-fill">
                                                        <input type="hidden" name="action" value="delete-category">
                                                        <input type="hidden" name="current_page" value="category-management">
                                                        <input type="hidden" name="category_id" value="<?= htmlspecialchars((string) $category['id']) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill w-100" onclick="return confirm('Delete this category?');">Del</button>
                                                    </form>
                                                </div>
                                            </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

