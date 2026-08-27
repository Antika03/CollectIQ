<?php $__env->startSection('title', 'Risk Score & Early Warning'); ?>
<?php $__env->startSection('subtitle', 'Analisis risiko collection & churn pelanggan berdasarkan scoring rule-based C3MR'); ?>

<?php $__env->startSection('content'); ?>


<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100" style="border-left: 4px solid var(--danger);">
            <div>
                <div class="kpi-label">Critical Action</div>
                <div class="kpi-value" style="color:var(--danger);"><?php echo e(number_format($riskCounts['CRITICAL'] ?? 0)); ?></div>
                <div class="kpi-sub">Score &ge; 70 (Intervensi Retensi)</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-exclamation-octagon-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100" style="border-left: 4px solid var(--warning);">
            <div>
                <div class="kpi-label">High Priority</div>
                <div class="kpi-value" style="color:var(--warning);"><?php echo e(number_format($riskCounts['HIGH'] ?? 0)); ?></div>
                <div class="kpi-sub">Score 45–69 (Prioritas Penagihan)</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-shield-exclamation"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100" style="border-left: 4px solid #CA8A04;">
            <div>
                <div class="kpi-label">Medium Priority</div>
                <div class="kpi-value" style="color:#A16207;"><?php echo e(number_format($riskCounts['MEDIUM'] ?? 0)); ?></div>
                <div class="kpi-sub">Score 25–44 (Caring Ulang / Monitoring)</div>
            </div>
            <div class="kpi-icon" style="background:#FEFCE8; color:#CA8A04;">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100" style="border-left: 4px solid var(--success);">
            <div>
                <div class="kpi-label">Low / Routine</div>
                <div class="kpi-value" style="color:var(--success);"><?php echo e(number_format($riskCounts['LOW'] ?? 0)); ?></div>
                <div class="kpi-sub">Score &lt; 25 (Pelayanan Reguler)</div>
            </div>
            <div class="kpi-icon" style="background:var(--success-soft); color:var(--success);">
                <i class="bi bi-shield-check"></i>
            </div>
        </div>
    </div>
</div>


<div class="filter-bar mb-3">
    <form method="GET" action="<?php echo e(url('/risk-score')); ?>" class="d-flex w-100 gap-2 flex-wrap align-items-end">
        <div style="flex:2; min-width:200px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Pencarian Pelanggan</label>
            <input type="text" name="search" value="<?php echo e($search); ?>" class="form-control form-control-sm"
                   placeholder="Cari nama, nomor internet, no HP, atau datel...">
        </div>
        <div style="flex:1; min-width:140px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Klasifikasi Risiko</label>
            <select name="risk_level" class="form-select form-select-sm">
                <option value="">Semua Level</option>
                <option value="CRITICAL" <?php echo e($filterLevel == 'CRITICAL' ? 'selected' : ''); ?>>🔴 Critical</option>
                <option value="HIGH" <?php echo e($filterLevel == 'HIGH' ? 'selected' : ''); ?>>🟠 High</option>
                <option value="MEDIUM" <?php echo e($filterLevel == 'MEDIUM' ? 'selected' : ''); ?>>🟡 Medium</option>
                <option value="LOW" <?php echo e($filterLevel == 'LOW' ? 'selected' : ''); ?>>🟢 Low</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-telkom" style="height:31px; font-size:12.5px; padding:4px 14px;">
                <i class="bi bi-funnel"></i> Apply
            </button>
            <?php if($search || $filterLevel): ?>
                <a href="<?php echo e(url('/risk-score')); ?>" class="btn btn-outline-secondary btn-sm" style="border-radius:8px; height:31px;">Reset</a>
            <?php endif; ?>
            <a href="<?php echo e(route('risk-score.export', request()->query())); ?>" class="btn btn-sm" style="background:var(--success-soft); color:var(--success); border:1px solid rgba(22,163,74,0.3); font-weight:600; border-radius:8px; height:31px; display:inline-flex; align-items:center; gap:5px;">
                <i class="bi bi-file-earmark-excel"></i> Export CSV
            </a>
        </div>
    </form>
</div>


<div class="card p-0" style="overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div>
            <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Daftar Evaluasi Risiko Pelanggan</div>
            <div style="font-size:11.5px; color:var(--ink-400);"><?php echo e($customers->total()); ?> pelanggan terdaftar</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Pelanggan</th>
                    <th>Nomor Internet</th>
                    <th>No. HP</th>
                    <th>Datel</th>
                    <th style="text-align:right;">Saldo Piutang</th>
                    <th style="text-align:center;">Score</th>
                    <th style="text-align:center;">Risk Level</th>
                    <th>Rekomendasi Tindakan</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td style="color:var(--ink-400); font-weight:600; text-align:center;">
                            <?php echo e($customers->firstItem() + $idx); ?>

                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="width:30px; height:30px; font-size:11px;">
                                    <?php echo e(strtoupper(substr($customer->nama_pelanggan ?? '-', 0, 2))); ?>

                                </div>
                                <div>
                                    <div style="font-weight:700; color:var(--ink-900); max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?php echo e($customer->nama_pelanggan); ?>

                                    </div>
                                    <div style="font-size:11px; color:var(--ink-400);">
                                        <?php echo e($customer->nama_layanan_internet ?? 'Internet'); ?>

                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">
                                <?php echo e($customer->nomor_internet); ?>

                            </code>
                        </td>
                        <td style="font-size:12px; color:var(--ink-700); white-space:nowrap;">
                            <?php if($customer->wa_url): ?>
                                <a href="<?php echo e($customer->wa_url); ?>" target="_blank" class="text-success text-decoration-none fw-semibold" title="Kirim WhatsApp Otomatis" data-bs-toggle="tooltip">
                                    <i class="bi bi-whatsapp"></i> <?php echo e($customer->no_hp_terbaru); ?>

                                </a>
                            <?php else: ?>
                                <?php echo e($customer->no_hp_terbaru ?: '-'); ?>

                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px; color:var(--ink-500);">
                            <?php echo e($customer->datel ?: ($customer->sto ?: '-')); ?>

                        </td>
                        <td style="text-align:right; font-weight:700; color:<?php echo e($customer->saldo_piutang > 0 ? 'var(--danger)' : 'var(--ink-400)'); ?>; font-size:13px; white-space:nowrap;">
                            <?php if($customer->saldo_piutang > 0): ?>
                                Rp <?php echo e(number_format($customer->saldo_piutang, 0, ',', '.')); ?>

                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center; font-weight:800; font-size:14px; color:var(--ink-900);">
                            <?php echo e($customer->risk_score_calc); ?>

                        </td>
                        <td style="text-align:center;">
                            <?php if($customer->risk_level_calc === 'CRITICAL'): ?>
                                <span class="badge-status badge-risk-critical">CRITICAL</span>
                            <?php elseif($customer->risk_level_calc === 'HIGH'): ?>
                                <span class="badge-status badge-risk-high">HIGH</span>
                            <?php elseif($customer->risk_level_calc === 'MEDIUM'): ?>
                                <span class="badge-status badge-risk-medium">MEDIUM</span>
                            <?php else: ?>
                                <span class="badge-status badge-risk-low">LOW</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size:12px; font-weight:600; color:var(--ink-900);">
                                <?php echo e($customer->recommendation); ?>

                            </div>
                            <?php if(!empty($customer->risk_reasons)): ?>
                                <div style="font-size:11px; color:var(--ink-400); margin-top:2px;">
                                    <?php echo e(implode(', ', array_slice($customer->risk_reasons, 0, 2))); ?>

                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <a href="<?php echo e(url('/customers/' . $customer->id)); ?>" class="btn btn-sm btn-outline-telkom" style="font-size:11.5px; padding:3px 8px; white-space:nowrap;" title="Lihat detail risiko pelanggan" data-bs-toggle="tooltip">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <i class="bi bi-shield-check"></i> Tidak ada data risiko pelanggan yang sesuai.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:14px 20px; border-top:1px solid var(--border);">
        <?php echo e($customers->links()); ?>

    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/risk-score/index.blade.php ENDPATH**/ ?>