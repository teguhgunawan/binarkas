<?php
$debtFormData   = $debtFormData   ?? [
    'name' => '', 'debt_type' => 'credit_card', 'due' => '', 'amount' => '0', 'status' => 'safe',
    'cc_last_four' => '', 'cc_expiry' => '', 'cc_billing_day' => '', 'cc_due_day' => '',
    'loan_principal' => '0', 'loan_installment' => '0', 'loan_tenure_months' => '',
    'loan_paid_months' => '0', 'loan_due_day' => '', 'interest_rate' => '',
    'od_usage_start_date' => '', 'od_due_date' => '',
];
$debtFormErrors = $debtFormErrors ?? [];
$flash          = $flash          ?? null;

function debt_overdraft_accrued(array $debt): float
{
    $rate = (float) ($debt['interest_rate'] ?? 0);
    if ($rate <= 0 || empty($debt['od_usage_start_date'])) {
        return 0.0;
    }
    try {
        $days = (int) (new DateTime('today'))->diff(new DateTime($debt['od_usage_start_date']))->days;
    } catch (Throwable) {
        return 0.0;
    }
    return (float) $debt['amount'] * ($rate / 100 / 365) * $days;
}

function debt_overdraft_days(array $debt): int
{
    if (empty($debt['od_usage_start_date'])) {
        return 0;
    }
    try {
        return (int) (new DateTime('today'))->diff(new DateTime($debt['od_usage_start_date']))->days;
    } catch (Throwable) {
        return 0;
    }
}
?>

<?php if (!empty($flash)): ?>
    <div class="alert <?= !empty($flash['ok']) ? 'alert-success' : 'alert-danger' ?> border-0 rounded-4 mb-4" role="alert">
        <?= htmlspecialchars($flash['message'] ?? 'Selesai.') ?>
    </div>
<?php endif; ?>

<!-- ======================================================
     FORM TAMBAH HUTANG
     ====================================================== -->
<section class="surface-card mb-4">
    <div class="mb-4">
        <h3 class="h5 mb-1">Tambah Hutang</h3>
        <p class="text-muted mb-0">Pilih tipe hutang terlebih dahulu, lalu isi detail yang sesuai.</p>
    </div>
    <form method="post" action="<?= htmlspecialchars(app_base('?page=debts')) ?>" id="form-create-debt">
        <input type="hidden" name="action" value="create">

        <!-- Baris 1: tipe, nama, saldo, status -->
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Tipe Hutang</label>
                <select name="debt_type" class="form-select rounded-4 debt-type-switcher <?= isset($debtFormErrors['debt_type']) ? 'is-invalid' : '' ?>">
                    <?php foreach (debt_type_options() as $val => $label): ?>
                        <option value="<?= htmlspecialchars($val) ?>" <?= ($debtFormData['debt_type'] ?? 'credit_card') === $val ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($debtFormErrors['debt_type'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['debt_type']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nama Kartu / Bank / Rekening</label>
                <input type="text" name="name" class="form-control rounded-4 <?= isset($debtFormErrors['name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $debtFormData['name']) ?>" placeholder="contoh: BCA Visa, KPR Mandiri">
                <?php if (isset($debtFormErrors['name'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Saldo / Tagihan Saat Ini</label>
                <input type="text" name="amount" class="form-control rounded-4 <?= isset($debtFormErrors['amount']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $debtFormData['amount']) ?>">
                <?php if (isset($debtFormErrors['amount'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['amount']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select rounded-4 <?= isset($debtFormErrors['status']) ? 'is-invalid' : '' ?>">
                    <?php foreach (debt_status_options() as $opt): ?>
                        <option value="<?= htmlspecialchars($opt) ?>" <?= ($debtFormData['status'] ?? 'safe') === $opt ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($opt)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($debtFormErrors['status'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['status']) ?></div><?php endif; ?>
            </div>
        </div>

        <!-- SEKSI KARTU KREDIT -->
        <div class="debt-type-section debt-fields-credit-card border rounded-4 p-3 mb-3 bg-light-subtle">
            <div class="small text-muted fw-semibold mb-2 text-uppercase ls-1">Detail Kartu Kredit</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">4 Digit Terakhir Kartu</label>
                    <input type="text" name="cc_last_four" class="form-control rounded-4 <?= isset($debtFormErrors['cc_last_four']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['cc_last_four'] ?? '')) ?>" maxlength="4" placeholder="1234">
                    <?php if (isset($debtFormErrors['cc_last_four'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['cc_last_four']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Masa Aktif (MM/YYYY)</label>
                    <input type="text" name="cc_expiry" class="form-control rounded-4 <?= isset($debtFormErrors['cc_expiry']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['cc_expiry'] ?? '')) ?>" placeholder="08/2029" maxlength="7">
                    <?php if (isset($debtFormErrors['cc_expiry'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['cc_expiry']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Cetak Tagihan</label>
                    <div class="input-group">
                        <input type="number" name="cc_billing_day" class="form-control rounded-start-4 <?= isset($debtFormErrors['cc_billing_day']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['cc_billing_day'] ?? '')) ?>" min="1" max="31" placeholder="25">
                        <span class="input-group-text rounded-end-4">tiap bulan</span>
                    </div>
                    <?php if (isset($debtFormErrors['cc_billing_day'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($debtFormErrors['cc_billing_day']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Jatuh Tempo</label>
                    <div class="input-group">
                        <input type="number" name="cc_due_day" class="form-control rounded-start-4 <?= isset($debtFormErrors['cc_due_day']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['cc_due_day'] ?? '')) ?>" min="1" max="31" placeholder="10">
                        <span class="input-group-text rounded-end-4">tiap bulan</span>
                    </div>
                    <?php if (isset($debtFormErrors['cc_due_day'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($debtFormErrors['cc_due_day']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SEKSI CICILAN -->
        <div class="debt-type-section debt-fields-installment border rounded-4 p-3 mb-3 bg-light-subtle" style="display:none">
            <div class="small text-muted fw-semibold mb-2 text-uppercase ls-1">Detail Hutang Cicilan</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Pokok Pinjaman</label>
                    <input type="text" name="loan_principal" class="form-control rounded-4 <?= isset($debtFormErrors['loan_principal']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['loan_principal'] ?? '0')) ?>">
                    <?php if (isset($debtFormErrors['loan_principal'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['loan_principal']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Angsuran Bulanan</label>
                    <input type="text" name="loan_installment" class="form-control rounded-4 <?= isset($debtFormErrors['loan_installment']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['loan_installment'] ?? '0')) ?>">
                    <?php if (isset($debtFormErrors['loan_installment'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['loan_installment']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tenor (bulan)</label>
                    <input type="number" name="loan_tenure_months" class="form-control rounded-4 <?= isset($debtFormErrors['loan_tenure_months']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['loan_tenure_months'] ?? '')) ?>" min="1" placeholder="60">
                    <?php if (isset($debtFormErrors['loan_tenure_months'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['loan_tenure_months']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sudah Dibayar (bln)</label>
                    <input type="number" name="loan_paid_months" class="form-control rounded-4 <?= isset($debtFormErrors['loan_paid_months']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['loan_paid_months'] ?? '0')) ?>" min="0">
                    <?php if (isset($debtFormErrors['loan_paid_months'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['loan_paid_months']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Suku Bunga (% p.a.)</label>
                    <div class="input-group">
                        <input type="text" name="interest_rate" class="form-control rounded-start-4 <?= isset($debtFormErrors['interest_rate']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['interest_rate'] ?? '')) ?>" placeholder="12.5">
                        <span class="input-group-text rounded-end-4">% p.a.</span>
                    </div>
                    <?php if (isset($debtFormErrors['interest_rate'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($debtFormErrors['interest_rate']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jatuh Tempo Tgl</label>
                    <div class="input-group">
                        <input type="number" name="loan_due_day" class="form-control rounded-start-4 <?= isset($debtFormErrors['loan_due_day']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['loan_due_day'] ?? '')) ?>" min="1" max="31" placeholder="15">
                        <span class="input-group-text rounded-end-4">tiap bln</span>
                    </div>
                    <?php if (isset($debtFormErrors['loan_due_day'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($debtFormErrors['loan_due_day']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SEKSI REKENING KORAN / OVERDRAFT -->
        <div class="debt-type-section debt-fields-overdraft border rounded-4 p-3 mb-3 bg-light-subtle" style="display:none">
            <div class="small text-muted fw-semibold mb-2 text-uppercase ls-1">Detail Rekening Koran / Overdraft</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Suku Bunga Tahunan (% p.a.)</label>
                    <div class="input-group">
                        <input type="text" name="interest_rate" class="form-control rounded-start-4 <?= isset($debtFormErrors['interest_rate']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['interest_rate'] ?? '')) ?>" placeholder="18">
                        <span class="input-group-text rounded-end-4">% p.a.</span>
                    </div>
                    <?php if (isset($debtFormErrors['interest_rate'])): ?><div class="text-danger small mt-1"><?= htmlspecialchars($debtFormErrors['interest_rate']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Mulai Pemakaian</label>
                    <input type="date" name="od_usage_start_date" class="form-control rounded-4 <?= isset($debtFormErrors['od_usage_start_date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['od_usage_start_date'] ?? '')) ?>">
                    <?php if (isset($debtFormErrors['od_usage_start_date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['od_usage_start_date']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Jatuh Tempo</label>
                    <input type="date" name="od_due_date" class="form-control rounded-4 <?= isset($debtFormErrors['od_due_date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['od_due_date'] ?? '')) ?>">
                    <?php if (isset($debtFormErrors['od_due_date'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['od_due_date']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SEKSI UMUM (backward compat) -->
        <div class="debt-type-section debt-fields-general border rounded-4 p-3 mb-3 bg-light-subtle" style="display:none">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Due Label</label>
                    <input type="text" name="due" class="form-control rounded-4 <?= isset($debtFormErrors['due']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) ($debtFormData['due'] ?? '')) ?>" placeholder="contoh: 28 Mar 2026">
                    <?php if (isset($debtFormErrors['due'])): ?><div class="invalid-feedback"><?= htmlspecialchars($debtFormErrors['due']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Hutang</button>
    </form>
</section>

<!-- ======================================================
     DEBT REGISTER
     ====================================================== -->
<section class="surface-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h3 class="h5 mb-0">Daftar Hutang</h3>
        <div class="text-muted small"><?= count($debts) ?> entri aktif</div>
    </div>
    <div class="vstack gap-3">
        <?php foreach ($debts as $debt):
            $dtype = $debt['debt_type'] ?? 'general';
            $typeLabels = debt_type_options();
            $typeLabel  = $typeLabels[$dtype] ?? 'Umum';

            $accrued     = 0.0;
            $accruedDays = 0;
            if ($dtype === 'overdraft') {
                $accrued     = debt_overdraft_accrued($debt);
                $accruedDays = debt_overdraft_days($debt);
            }
        ?>
        <div class="rounded-4 bg-light-subtle p-3">

            <!-- Header ringkasan -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge text-bg-secondary"><?= htmlspecialchars($typeLabel) ?></span>
                <strong><?= htmlspecialchars($debt['name']) ?></strong>
                <span class="badge text-bg-<?= htmlspecialchars(debt_badge_class($debt['status'])) ?>"><?= ucfirst($debt['status']) ?></span>
                <span class="ms-auto fw-semibold text-primary"><?= htmlspecialchars(format_idr((float) $debt['amount'])) ?></span>
            </div>

            <!-- Info spesifik per tipe -->
            <?php if ($dtype === 'credit_card'): ?>
                <div class="text-muted small mb-2">
                    Kartu: <strong>**** **** **** <?= htmlspecialchars((string) ($debt['cc_last_four'] ?? '????')) ?></strong>
                    &nbsp;|&nbsp; Berlaku s/d: <strong><?= htmlspecialchars((string) ($debt['cc_expiry'] ?? '-')) ?></strong>
                    &nbsp;|&nbsp; Cetak tgl <strong><?= htmlspecialchars((string) ($debt['cc_billing_day'] ?? '-')) ?></strong>
                    &nbsp;|&nbsp; Jatuh tempo tgl <strong><?= htmlspecialchars((string) ($debt['cc_due_day'] ?? '-')) ?></strong>
                </div>

            <?php elseif ($dtype === 'installment'):
                $tenure = (int) ($debt['loan_tenure_months'] ?? 0);
                $paid   = (int) ($debt['loan_paid_months'] ?? 0);
                $sisa   = max(0, $tenure - $paid);
                $pct    = $tenure > 0 ? round(($paid / $tenure) * 100) : 0;
            ?>
                <div class="text-muted small mb-1">
                    Pokok: <strong><?= format_idr((float) ($debt['loan_principal'] ?? 0)) ?></strong>
                    &nbsp;|&nbsp; Angsuran: <strong><?= format_idr((float) ($debt['loan_installment'] ?? 0)) ?>/bln</strong>
                    &nbsp;|&nbsp; Sisa: <strong><?= $sisa ?>/<?= $tenure ?> bln</strong>
                    &nbsp;|&nbsp; Bunga: <strong><?= number_format((float) ($debt['interest_rate'] ?? 0), 2) ?>% p.a.</strong>
                    &nbsp;|&nbsp; Angsuran tgl <strong><?= htmlspecialchars((string) ($debt['loan_due_day'] ?? '-')) ?></strong>
                </div>
                <div class="progress mb-2" style="height:6px" title="<?= $pct ?>% terbayar">
                    <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                </div>

            <?php elseif ($dtype === 'overdraft'): ?>
                <div class="text-muted small mb-2">
                    Bunga: <strong><?= number_format((float) ($debt['interest_rate'] ?? 0), 2) ?>% p.a.</strong>
                    &nbsp;|&nbsp; Mulai: <strong><?= htmlspecialchars((string) ($debt['od_usage_start_date'] ?? '-')) ?></strong>
                    &nbsp;|&nbsp; Jatuh tempo: <strong><?= htmlspecialchars((string) ($debt['od_due_date'] ?? '-')) ?></strong>
                    &nbsp;|&nbsp; Bunga terakumulasi (<?= $accruedDays ?> hari):
                    <span class="text-danger fw-semibold"><?= format_idr($accrued) ?></span>
                </div>

            <?php else: ?>
                <div class="text-muted small mb-2">
                    Jatuh tempo: <strong><?= htmlspecialchars($debt['due']) ?></strong>
                </div>
            <?php endif; ?>

            <!-- Form inline edit -->
            <form method="post" action="<?= htmlspecialchars(app_base('?page=debts')) ?>" class="border-top pt-3 mt-1">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="debt_id" value="<?= htmlspecialchars((string) $debt['id']) ?>">

                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <label class="form-label small">Tipe</label>
                        <select name="debt_type" class="form-select form-select-sm rounded-4 debt-type-switcher">
                            <?php foreach (debt_type_options() as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>" <?= $dtype === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Nama</label>
                        <input type="text" name="name" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars($debt['name']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Saldo / Tagihan</label>
                        <input type="text" name="amount" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) $debt['amount']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm rounded-4">
                            <?php foreach (debt_status_options() as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" <?= $debt['status'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Edit: Kartu Kredit -->
                <div class="debt-type-section debt-fields-credit-card bg-white border rounded-4 p-2 mb-2 <?= $dtype !== 'credit_card' ? 'd-none-section' : '' ?>">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">4 Digit Terakhir</label>
                            <input type="text" name="cc_last_four" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['cc_last_four'] ?? '')) ?>" maxlength="4">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Masa Aktif (MM/YYYY)</label>
                            <input type="text" name="cc_expiry" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['cc_expiry'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Cetak Tgl</label>
                            <input type="number" name="cc_billing_day" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['cc_billing_day'] ?? '')) ?>" min="1" max="31">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Jatuh Tempo Tgl</label>
                            <input type="number" name="cc_due_day" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['cc_due_day'] ?? '')) ?>" min="1" max="31">
                        </div>
                    </div>
                </div>

                <!-- Edit: Cicilan -->
                <div class="debt-type-section debt-fields-installment bg-white border rounded-4 p-2 mb-2 <?= $dtype !== 'installment' ? 'd-none-section' : '' ?>">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Pokok Pinjaman</label>
                            <input type="text" name="loan_principal" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['loan_principal'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Angsuran Bulanan</label>
                            <input type="text" name="loan_installment" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['loan_installment'] ?? '')) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Tenor (bln)</label>
                            <input type="number" name="loan_tenure_months" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['loan_tenure_months'] ?? '')) ?>" min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Sudah Bayar (bln)</label>
                            <input type="number" name="loan_paid_months" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['loan_paid_months'] ?? '0')) ?>" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Bunga (% p.a.)</label>
                            <input type="text" name="interest_rate" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['interest_rate'] ?? '')) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">JT Tgl</label>
                            <input type="number" name="loan_due_day" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['loan_due_day'] ?? '')) ?>" min="1" max="31">
                        </div>
                    </div>
                </div>

                <!-- Edit: Overdraft -->
                <div class="debt-type-section debt-fields-overdraft bg-white border rounded-4 p-2 mb-2 <?= $dtype !== 'overdraft' ? 'd-none-section' : '' ?>">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Bunga (% p.a.)</label>
                            <input type="text" name="interest_rate" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['interest_rate'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Mulai Pemakaian</label>
                            <input type="date" name="od_usage_start_date" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['od_usage_start_date'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Jatuh Tempo</label>
                            <input type="date" name="od_due_date" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars((string) ($debt['od_due_date'] ?? '')) ?>">
                        </div>
                    </div>
                </div>

                <!-- Edit: Umum -->
                <div class="debt-type-section debt-fields-general bg-white border rounded-4 p-2 mb-2 <?= $dtype !== 'general' ? 'd-none-section' : '' ?>">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Due Label</label>
                            <input type="text" name="due" class="form-control form-control-sm rounded-4" value="<?= htmlspecialchars($debt['due']) ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3">Simpan</button>
            </form>

            <!-- Delete -->
            <div class="pt-2">
                <form method="post" action="<?= htmlspecialchars(app_base('?page=debts')) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="debt_id" value="<?= htmlspecialchars((string) $debt['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Hapus hutang ini?')">Hapus</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($debts)): ?>
            <div class="text-muted text-center py-4">Belum ada data hutang.</div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    function switchDebtFields(selectEl) {
        const form = selectEl.closest('form');
        form.querySelectorAll('.debt-type-section').forEach(function (el) {
            el.style.display = 'none';
            el.querySelectorAll('input, select, textarea').forEach(function (f) { f.disabled = true; });
        });
        const key    = selectEl.value.replace(/_/g, '-');
        const active = form.querySelector('.debt-fields-' + key);
        if (active) {
            active.style.display = '';
            active.querySelectorAll('input, select, textarea').forEach(function (f) { f.disabled = false; });
        }
    }

    function initDebtSections() {
        // For register rows: honour the d-none-section CSS class on initial load
        document.querySelectorAll('.d-none-section').forEach(function (el) {
            el.style.display = 'none';
            el.querySelectorAll('input, select, textarea').forEach(function (f) { f.disabled = true; });
        });

        document.querySelectorAll('select.debt-type-switcher').forEach(function (sel) {
            switchDebtFields(sel);
            sel.addEventListener('change', function () { switchDebtFields(this); });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDebtSections);
    } else {
        initDebtSections();
    }
}());
</script>
