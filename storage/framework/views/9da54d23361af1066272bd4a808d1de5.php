<?php $__env->startSection('title', 'AR Agents Performance'); ?>
<?php $__env->startSection('subtitle', 'Monitoring performansi dan produktivitas tim Account Receivable (AR) Lapangan'); ?>

<?php $__env->startSection('content'); ?>


<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total AR Agent</div>
                <div class="kpi-value"><?php echo e(number_format($totalAgents)); ?></div>
                <div class="kpi-sub">Master petugas collection</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-person-badge-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Agent Aktif</div>
                <div class="kpi-value" style="color:var(--success);"><?php echo e(number_format($activeAgents)); ?></div>
                <div class="kpi-sub"><i class="bi bi-check-circle-fill"></i> Siap penugasan visit</div>
            </div>
            <div class="kpi-icon" style="background:var(--success-soft); color:var(--success);">
                <i class="bi bi-person-check-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Kunjungan Visit</div>
                <div class="kpi-value"><?php echo e(number_format($totalVisits)); ?></div>
                <div class="kpi-sub">Seluruh riwayat penagihan</div>
            </div>
            <div class="kpi-icon" style="background:#EFF6FF; color:#2563EB;">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">PTP Terkumpul</div>
                <div class="kpi-value" style="color:var(--warning);"><?php echo e(number_format($totalPtp)); ?></div>
                <div class="kpi-sub">
                    <?php echo e($totalVisits > 0 ? round(($totalPtp / $totalVisits) * 100, 1) : 0); ?>% PTP Conversion Rate
                </div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>
</div>


<div class="filter-bar mb-3">
    <form method="GET" action="<?php echo e(url('/ar-agents')); ?>" class="d-flex w-100 gap-2 flex-wrap align-items-center">
        <div class="flex-grow-1" style="min-width:220px;">
            <input type="text" name="search" value="<?php echo e($search); ?>" class="form-control form-control-sm"
                   placeholder="Cari nama AR Agent...">
        </div>
        <button type="submit" class="btn btn-primary-telkom" style="height:31px; font-size:12.5px; padding:4px 14px;">
            <i class="bi bi-search"></i> Cari
        </button>
        <?php if($search): ?>
            <a href="<?php echo e(url('/ar-agents')); ?>" class="btn btn-outline-secondary btn-sm" style="border-radius:8px; height:31px;">Reset</a>
        <?php endif; ?>
        <a href="<?php echo e(route('ar-agents.export', request()->query())); ?>" class="btn btn-sm" style="background:var(--success-soft); color:var(--success); border:1px solid rgba(22,163,74,0.3); font-weight:600; border-radius:8px; height:31px; display:inline-flex; align-items:center; gap:5px;">
            <i class="bi bi-file-earmark-excel"></i> Export CSV
        </a>
    </form>
</div>


<div class="card p-0" style="overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div>
            <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Klasemen &amp; Produktivitas AR Agent</div>
            <div style="font-size:11.5px; color:var(--ink-400);">Daftar seluruh petugas collection lapangan</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">Rank</th>
                    <th>Nama AR Agent</th>
                    <th style="text-align:center;">Total Visit</th>
                    <th style="text-align:center;">Pelanggan Unik</th>
                    <th style="text-align:center;">Contacted</th>
                    <th style="text-align:center;">PTP</th>
                    <th style="width:160px;">PTP Rate</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $agents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td style="text-align:center;">
                            <?php $rank = $agents->firstItem() + $idx; ?>
                            <span class="rank-pill rank-<?php echo e($rank <= 3 ? $rank : 'other'); ?>" style="margin:0 auto;">
                                <?php echo e($rank); ?>

                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle">
                                    <?php echo e(strtoupper(substr($agent->name, 0, 2))); ?>

                                </div>
                                <div>
                                    <div style="font-weight:700; color:var(--ink-900);">
                                        <?php echo e($agent->name); ?>

                                    </div>
                                    <?php if($agent->chat_id_telegram): ?>
                                        <div style="font-size:11px; color:#227093;">
                                            <i class="bi bi-telegram"></i> Telegram connected
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:center; font-weight:800; font-size:14px; color:var(--ink-900);">
                            <?php echo e($agent->visits_count); ?>

                        </td>
                        <td style="text-align:center; font-weight:600; color:var(--ink-700);">
                            <?php echo e($agent->unique_customers ?? 0); ?>

                        </td>
                        <td style="text-align:center;">
                            <span class="badge-status badge-contacted">
                                <?php echo e($agent->contacted_count); ?> (<?php echo e($agent->contacted_rate); ?>%)
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-status badge-ptp">
                                <?php echo e($agent->ptp_count); ?>

                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="flex:1; height:6px; background:var(--secondary); border-radius:99px; overflow:hidden;">
                                    <div style="width:<?php echo e($agent->ptp_rate); ?>%; height:100%; background:linear-gradient(90deg, #F59E0B, #D97706); border-radius:99px;"></div>
                                </div>
                                <span style="font-size:11.5px; font-weight:700; color:var(--warning); min-width:36px; text-align:right;">
                                    <?php echo e($agent->ptp_rate); ?>%
                                </span>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <?php if($agent->is_active): ?>
                                <span class="badge-status" style="background:#D1FAE5; color:#065F46;">Aktif</span>
                            <?php else: ?>
                                <span class="badge-status" style="background:var(--secondary); color:var(--ink-400);">Non-Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <a href="<?php echo e(url('/visits?ar_agent_id=' . $agent->id)); ?>" class="btn btn-sm btn-outline-telkom"
                               style="font-size:11.5px; padding:3px 8px; white-space:nowrap;"
                               title="Lihat Rekap Kunjungan Visit Petugas" data-bs-toggle="tooltip">
                                <i class="bi bi-geo-alt"></i> Visit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="bi bi-person-x"></i> Tidak ada data AR Agent ditemukan.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:14px 20px; border-top:1px solid var(--border);">
        <?php echo e($agents->links()); ?>

    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/ar-agents/index.blade.php ENDPATH**/ ?>