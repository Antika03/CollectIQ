<?php $__env->startSection('title', 'Executive Command Center'); ?>
<?php $__env->startSection('subtitle', 'Monitoring performansi collection, pemulihan piutang, dan mitigasi risiko churn pelanggan Telkom'); ?>

<?php $__env->startSection('content'); ?>


<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Customer</div>
                <div class="kpi-value"><?php echo e(number_format($totalCustomers)); ?></div>
                <div class="kpi-sub">Master Data C3MR PRITI</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Piutang (Outstanding)</div>
                <div class="kpi-value" style="font-size:22px; color:var(--danger); white-space:nowrap;">Rp <?php echo e(number_format($totalPiutang, 0, ',', '.')); ?></div>
                <div class="kpi-sub">Saldo tertahan di pelanggan</div>
            </div>
            <div class="kpi-icon" style="background:var(--danger-soft); color:var(--danger);">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Kunjungan Visit</div>
                <div class="kpi-value"><?php echo e(number_format($totalVisits)); ?></div>
                <div class="kpi-sub">Hari ini: <strong>+<?php echo e($todayVisits); ?></strong> kunjungan</div>
            </div>
            <div class="kpi-icon" style="background:#EFF6FF; color:#2563EB;">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Janji Bayar (PTP)</div>
                <div class="kpi-value"><?php echo e(number_format($totalPtp)); ?></div>
                <div class="kpi-sub">Hari ini: <strong>+<?php echo e($todayPTP); ?></strong> PTP</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>
</div>


<div class="card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="section-title">
                <i class="bi bi-lightning-charge-fill" style="color:var(--primary); margin-right:6px;"></i>
                Action Required — Prioritas Tindakan Collection &amp; Retensi
            </div>
            <div class="section-sub">Klasifikasi penanganan pelanggan berdasarkan Early Warning Churn Risk Indicator</div>
        </div>
        <a href="<?php echo e(url('/c3mr/performance')); ?>" class="btn btn-outline-telkom btn-sm">
            Lihat Analisis Detail <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--danger-soft); border:1px solid rgba(220,38,38,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--danger);">🔴 CRITICAL ACTION</span>
                    <span style="font-size:18px; font-weight:800; color:var(--danger);"><?php echo e($criticalCount); ?></span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Permintaan cabut / tunggakan kritis. <strong>Intervensi winback segera.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--warning-soft); border:1px solid rgba(217,119,6,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--warning);">🟠 HIGH PRIORITY</span>
                    <span style="font-size:18px; font-weight:800; color:var(--warning);"><?php echo e($highCount); ?></span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Broken PTP / saldo tinggi. <strong>Prioritas kunjungan visit AR.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:#FEFCE8; border:1px solid rgba(202,138,4,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:#A16207;">🟡 MEDIUM PRIORITY</span>
                    <span style="font-size:18px; font-weight:800; color:#A16207;"><?php echo e($mediumCount); ?></span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Uncontacted / RNA. <strong>Caring ulang via kanal alternatif.</strong>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="p-3" style="background:var(--success-soft); border:1px solid rgba(22,163,74,0.2); border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:11px; font-weight:800; color:var(--success);">🟢 LOW / ROUTINE</span>
                    <span style="font-size:18px; font-weight:800; color:var(--success);"><?php echo e($lowCount); ?></span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-700); margin-top:6px; line-height:1.4;">
                    Pelanggan lancar. <strong>Pelayanan reguler &amp; monitoring.</strong>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-title"><i class="bi bi-graph-up" style="color:var(--primary); margin-right:6px;"></i> Trend Kunjungan Visit &amp; Janji Bayar (14 Hari Terakhir)</div>
                    <div class="section-sub">Aktivitas penagihan lapangan harian</div>
                </div>
            </div>
            <canvas id="chartTrend" height="145"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-title"><i class="bi bi-award-fill" style="color:var(--warning); margin-right:6px;"></i> Top AR Agent</div>
                    <div class="section-sub">Produktivitas kunjungan visit</div>
                </div>
                <a href="<?php echo e(url('/ar-agents')); ?>" style="font-size:12px; color:var(--primary); font-weight:600; text-decoration:none;">Semua</a>
            </div>

            <div class="d-flex flex-column gap-3">
                <?php $__empty_1 = true; $__currentLoopData = $topAgents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rank-pill rank-<?php echo e($idx + 1 <= 3 ? ($idx + 1) : 'other'); ?>" style="width:22px; height:22px; font-size:11px;">
                                    <?php echo e($idx + 1); ?>

                                </span>
                                <span style="font-size:13px; font-weight:700; color:var(--ink-900);"><?php echo e($agent->name); ?></span>
                            </div>
                            <span style="font-size:12px; font-weight:700; color:var(--primary);"><?php echo e($agent->visits_count); ?> Visit</span>
                        </div>
                        <div style="height:6px; background:var(--secondary); border-radius:99px; overflow:hidden;">
                            <div style="width:<?php echo e($agent->contribution_percent); ?>%; height:100%; background:linear-gradient(90deg, var(--primary-light), var(--primary-dark)); border-radius:99px;"></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="empty-state py-3">Belum ada data AR Agent.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="row g-3">
    
    <div class="col-lg-6">
        <div class="card h-100 p-0" style="overflow:hidden;">
            <div style="padding:14px 18px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                    <i class="bi bi-exclamation-circle-fill" style="color:var(--danger); margin-right:6px;"></i> Top Saldo Piutang Menunggak
                </div>
                <a href="<?php echo e(url('/piutang')); ?>" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>No Internet</th>
                            <th style="text-align:right;">Saldo Piutang</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $topOutstanding; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:var(--ink-900);"><?php echo e($c->nama_pelanggan); ?></div>
                                    <div style="font-size:11px; color:var(--ink-400);"><?php echo e($c->datel ?: 'Wilayah Telkom'); ?></div>
                                </td>
                                <td><code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);"><?php echo e($c->nomor_internet); ?></code></td>
                                <td style="text-align:right; font-weight:800; color:var(--danger); white-space:nowrap;">
                                    Rp <?php echo e(number_format($c->saldo_piutang, 0, ',', '.')); ?>

                                </td>
                                <td style="text-align:center;">
                                    <a href="<?php echo e(url('/customers/' . $c->id)); ?>" class="btn btn-sm btn-outline-telkom" style="font-size:11.5px; padding:3px 8px; white-space:nowrap;" title="Lihat Detail Pelanggan" data-bs-toggle="tooltip">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div class="col-lg-6">
        <div class="card h-100 p-0" style="overflow:hidden;">
            <div style="padding:14px 18px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                    <i class="bi bi-camera-fill" style="color:var(--primary); margin-right:6px;"></i> Kunjungan Visit Lapangan Terbaru
                </div>
                <a href="<?php echo e(url('/visits')); ?>" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Lihat Semua <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="p-3">
                <?php $__currentLoopData = $latestVisits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border);">
                        <?php if($v->foto_url): ?>
                            <img src="<?php echo e(route('visit.photo', ['visit' => $v->id])); ?>"
                                 style="width:42px; height:42px; object-fit:cover; border-radius:8px; border:1px solid var(--border);"
                                 onerror="this.src='<?php echo e(asset('images/photo-placeholder.svg')); ?>'"
                                 alt="Foto visit">
                        <?php else: ?>
                            <div style="width:42px; height:42px; border-radius:8px; background:var(--secondary); display:flex; align-items:center; justify-content:center; color:var(--ink-400); flex-shrink:0;">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="font-weight:700; font-size:13px; color:var(--ink-900); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?php echo e(optional($v->customer)->nama_pelanggan ?? 'Pelanggan'); ?>

                                </span>
                                <span style="font-size:11px; color:var(--ink-400); white-space:nowrap; margin-left:8px;">
                                    <?php echo e($v->tanggal_input?->format('d M')); ?>

                                </span>
                            </div>
                            <div style="font-size:12px; color:var(--ink-500); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?php echo e($v->hasil_visit); ?> &middot; AR: <?php echo e(optional($v->arAgent)->name ?? '-'); ?>

                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartTrend');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels, 15, 512) ?>,
                datasets: [
                    {
                        label: 'Total Visit',
                        data: <?php echo json_encode($chartVisits, 15, 512) ?>,
                        borderColor: '#E2001A',
                        backgroundColor: 'rgba(226,0,26,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    },
                    {
                        label: 'Janji Bayar (PTP)',
                        data: <?php echo json_encode($chartPtp, 15, 512) ?>,
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245,158,11,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/dashboard.blade.php ENDPATH**/ ?>