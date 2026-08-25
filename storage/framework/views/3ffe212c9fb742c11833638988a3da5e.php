<?php $__env->startSection('title', 'PTP Monitoring'); ?>
<?php $__env->startSection('subtitle', 'Monitoring komitmen janji bayar (Promise To Pay) pelanggan Telkom'); ?>

<?php $__env->startSection('content'); ?>


<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Janji Bayar (PTP)</div>
                <div class="kpi-value" style="color:var(--warning);"><?php echo e(number_format($totalPtp)); ?></div>
                <div class="kpi-sub"><?php echo e($ptpRate); ?>% dari total kunjungan</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">PTP Hari Ini</div>
                <div class="kpi-value"><?php echo e(number_format($todayPtp)); ?></div>
                <div class="kpi-sub"><?php echo e(now()->translatedFormat('d F Y')); ?></div>
            </div>
            <div class="kpi-icon" style="background:#EFF6FF; color:#2563EB;">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">PTP Dengan Bukti Foto</div>
                <div class="kpi-value"><?php echo e(number_format($ptpWithPhoto)); ?></div>
                <div class="kpi-sub">Foto kunjungan lapangan terverifikasi</div>
            </div>
            <div class="kpi-icon" style="background:var(--success-soft); color:var(--success);">
                <i class="bi bi-camera-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Tingkat Komitmen (PTP Rate)</div>
                <div class="kpi-value"><?php echo e($ptpRate); ?>%</div>
                <div class="kpi-sub">Efektivitas kunjungan lapangan</div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
    </div>
</div>


<div class="filter-bar mb-3">
    <form method="GET" action="<?php echo e(url('/ptp-monitoring')); ?>" class="d-flex w-100 gap-2 flex-wrap align-items-end">
        <div style="flex:2; min-width:200px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Pencarian</label>
            <input type="text" name="search" value="<?php echo e(request('search')); ?>" class="form-control form-control-sm"
                   placeholder="Cari nama pelanggan atau nomor internet...">
        </div>
        <div style="flex:1; min-width:140px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">AR Agent</label>
            <select name="ar_agent_id" class="form-select form-select-sm">
                <option value="">Semua Agent</option>
                <?php $__currentLoopData = $arAgents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($agent->id); ?>" <?php echo e(request('ar_agent_id') == $agent->id ? 'selected' : ''); ?>>
                        <?php echo e($agent->name); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div style="flex:1; min-width:130px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Tanggal Mulai</label>
            <input type="date" name="date_from" value="<?php echo e(request('date_from')); ?>" class="form-control form-control-sm">
        </div>
        <div style="flex:1; min-width:130px;">
            <label class="form-label mb-1" style="font-size:11px; font-weight:700; color:var(--ink-500);">Tanggal Sampai</label>
            <input type="date" name="date_to" value="<?php echo e(request('date_to')); ?>" class="form-control form-control-sm">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary-telkom" style="height:31px; font-size:12.5px; padding:4px 14px;">
                <i class="bi bi-funnel"></i> Apply
            </button>
            <?php if(request()->anyFilled(['search', 'ar_agent_id', 'date_from', 'date_to'])): ?>
                <a href="<?php echo e(url('/ptp-monitoring')); ?>" class="btn btn-outline-secondary btn-sm" style="border-radius:8px; height:31px;">Reset</a>
            <?php endif; ?>
            <a href="<?php echo e(route('ptp.export', request()->query())); ?>" class="btn btn-sm" style="background:var(--success-soft); color:var(--success); border:1px solid rgba(22,163,74,0.3); font-weight:600; border-radius:8px; height:31px; display:inline-flex; align-items:center; gap:5px;">
                <i class="bi bi-file-earmark-excel"></i> Export CSV
            </a>
        </div>
    </form>
</div>


<div class="card p-0" style="overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div>
            <div style="font-weight:700; font-size:14px; color:var(--ink-900);">Daftar Komitmen Janji Bayar (PTP)</div>
            <div style="font-size:11.5px; color:var(--ink-400);"><?php echo e($ptps->total()); ?> komitmen ditemukan</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th style="width:50px;">Foto</th>
                    <th>Tanggal Visit</th>
                    <th>Pelanggan</th>
                    <th>Nomor Internet</th>
                    <th>No. HP</th>
                    <th>AR Agent</th>
                    <th>Hasil Visit</th>
                    <th>Keterangan</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $ptps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ptp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <?php if($ptp->foto_url): ?>
                                <img src="<?php echo e(route('visit.photo', ['visit' => $ptp->id])); ?>"
                                     class="photo-thumb"
                                     data-bs-toggle="modal"
                                     data-bs-target="#photoModal<?php echo e($ptp->id); ?>"
                                     onerror="this.src='<?php echo e(asset('images/photo-placeholder.svg')); ?>'"
                                     alt="Foto visit">

                                
                                <div class="modal fade" id="photoModal<?php echo e($ptp->id); ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content" style="border-radius:16px; border:none; background:var(--surface);">
                                            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                                                <h6 class="modal-title" style="font-weight:700; color:var(--ink-900);">
                                                    <i class="bi bi-camera-fill" style="color:var(--primary); margin-right:6px;"></i>
                                                    Foto Visit — <?php echo e($ptp->customer->nama_pelanggan ?? 'Pelanggan'); ?>

                                                </h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center p-3" style="background:#0F172A; border-radius:0 0 16px 16px;">
                                                <img src="<?php echo e(route('visit.photo', ['visit' => $ptp->id])); ?>"
                                                     style="max-width:100%; max-height:70vh; object-fit:contain; border-radius:8px;"
                                                     alt="Foto visit">
                                                <?php if($ptp->drive_url): ?>
                                                    <div class="mt-3">
                                                        <a href="<?php echo e($ptp->drive_url); ?>" target="_blank" class="btn btn-sm btn-outline-light" style="border-radius:8px; font-size:12px;">
                                                            <i class="bi bi-box-arrow-up-right"></i> Buka Asli di Google Drive
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="photo-placeholder"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12.5px; white-space:nowrap; color:var(--ink-700);">
                            <?php echo e(optional($ptp->tanggal_input)->translatedFormat('d M Y') ?? '-'); ?>

                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--ink-900);">
                                <?php echo e($ptp->customer->nama_pelanggan ?? '-'); ?>

                            </div>
                            <div style="font-size:11px; color:var(--ink-400);">
                                <?php echo e($ptp->customer->nama_layanan_internet ?? 'Internet'); ?>

                            </div>
                        </td>
                        <td>
                            <code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px; color:var(--ink-700);">
                                <?php echo e($ptp->customer->nomor_internet ?? '-'); ?>

                            </code>
                        </td>
                        <td style="font-size:12px; color:var(--ink-700); white-space:nowrap;">
                            <?php if($ptp->customer && $ptp->customer->wa_url): ?>
                                <a href="<?php echo e($ptp->customer->wa_url); ?>" target="_blank" class="text-success text-decoration-none fw-semibold" title="Kirim WhatsApp Otomatis" data-bs-toggle="tooltip">
                                    <i class="bi bi-whatsapp"></i> <?php echo e($ptp->no_hp_snapshot ?: $ptp->customer->no_hp_terbaru); ?>

                                </a>
                            <?php else: ?>
                                <?php echo e($ptp->no_hp_snapshot ?: ($ptp->customer->no_hp_terbaru ?? '-')); ?>

                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px; font-weight:600; color:var(--ink-700);">
                            <?php echo e($ptp->arAgent->name ?? '-'); ?>

                        </td>
                        <td>
                            <span class="badge-status badge-ptp">
                                <i class="bi bi-check2-circle"></i> <?php echo e($ptp->hasil_visit); ?>

                            </span>
                        </td>
                        <td style="font-size:12px; color:var(--ink-500); max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo e($ptp->keterangan_visit); ?>">
                            <?php echo e($ptp->keterangan_visit ?: '-'); ?>

                        </td>
                        <td style="text-align:center;">
                            <?php if($ptp->customer_id): ?>
                                <a href="<?php echo e(url('/customers/' . $ptp->customer_id)); ?>" class="btn btn-sm btn-outline-telkom"
                                   style="font-size:11.5px; padding:3px 8px; white-space:nowrap;"
                                   title="Lihat Detail Profil Pelanggan" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            <?php else: ?>
                                <span style="color:var(--ink-400); font-size:11px;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="bi bi-cash-coin"></i> Belum ada data PTP yang sesuai kriteria.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:14px 20px; border-top:1px solid var(--border);">
        <?php echo e($ptps->links()); ?>

    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/ptp-monitoring/index.blade.php ENDPATH**/ ?>