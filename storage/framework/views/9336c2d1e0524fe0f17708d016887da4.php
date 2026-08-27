<?php $__env->startSection('title', 'Hasil Pencarian'); ?>
<?php $__env->startSection('subtitle', 'Hasil pencarian untuk: "' . $q . '"'); ?>

<?php $__env->startSection('content'); ?>


<div class="d-flex align-items-center gap-3 mb-4">
    <div>
        <div style="font-size:20px; font-weight:800; color:var(--ink-900);">
            Hasil Pencarian <span style="color:var(--primary);">"<?php echo e($q); ?>"</span>
        </div>
        <div style="font-size:13px; color:var(--ink-400); margin-top:3px;">
            Ditemukan <strong><?php echo e($totalResults); ?></strong> hasil dari database — Customers, Visits, Caring Logs, AR Agents
        </div>
    </div>
</div>

<?php if($totalResults === 0): ?>
<div class="card">
    <div class="empty-state">
        <i class="bi bi-search"></i>
        Tidak ada hasil yang ditemukan untuk "<strong><?php echo e($q); ?></strong>".<br>
        <div style="margin-top:10px; font-size:13px;">
            Coba gunakan nomor internet, nama pelanggan, atau no HP.
        </div>
    </div>
</div>
<?php endif; ?>


<?php if($customers->count() > 0): ?>
<div class="card p-0 mb-4" style="overflow:hidden;">
    <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:700; font-size:14px; color:var(--ink-900);">
            <i class="bi bi-people-fill" style="color:var(--primary); margin-right:6px;"></i>
            Pelanggan (Customers)
        </div>
        <span class="badge-status badge-not-contacted"><?php echo e($customers->count()); ?> hasil</span>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th>Pelanggan</th>
                    <th>No Internet</th>
                    <th>No HP</th>
                    <th>Datel / STO</th>
                    <th style="text-align:right;">Saldo Piutang</th>
                    <th style="text-align:center;">Risk</th>
                    <th style="text-align:center;">Visit Terakhir</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-circle" style="width:30px; height:30px; font-size:11px;"><?php echo e(strtoupper(substr($c->nama_pelanggan, 0, 2))); ?></div>
                            <div>
                                <div style="font-weight:700; color:var(--ink-900);"><?php echo e($c->nama_pelanggan); ?></div>
                                <div style="font-size:11px; color:var(--ink-400);"><?php echo e($c->nama_layanan_internet ?: 'Internet'); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:12px; color:var(--ink-700); white-space:nowrap;">
                        <?php if($c->wa_url): ?>
                            <a href="<?php echo e($c->wa_url); ?>" target="_blank" class="text-success text-decoration-none fw-semibold" title="Kirim WhatsApp Otomatis" data-bs-toggle="tooltip">
                                <i class="bi bi-whatsapp"></i> <?php echo e($c->no_hp_terbaru); ?>

                            </a>
                        <?php else: ?>
                            <?php echo e($c->no_hp_terbaru ?: '-'); ?>

                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px; color:var(--ink-500);"><?php echo e($c->datel ?: ($c->sto ?: '-')); ?></td>
                    <td style="text-align:right; font-weight:700; color:<?php echo e($c->saldo_piutang > 0 ? 'var(--danger)' : 'var(--ink-400)'); ?>; white-space:nowrap;">
                        <?php if($c->saldo_piutang > 0): ?> Rp <?php echo e(number_format($c->saldo_piutang, 0, ',', '.')); ?> <?php else: ?> - <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if($c->risk_level): ?>
                            <span class="badge-status badge-risk-<?php echo e(strtolower($c->risk_level)); ?>" style="font-size:10px; padding:2px 7px;">
                                <?php echo e(strtoupper($c->risk_level)); ?>

                            </span>
                        <?php else: ?>
                            <span style="color:var(--ink-400); font-size:11px;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center; font-size:12px; color:var(--ink-500);">
                        <?php echo e($c->last_visit_at ? $c->last_visit_at->format('d/m/Y') : '-'); ?>

                    </td>
                    <td style="text-align:center;">
                        <a href="<?php echo e(url('/customers/' . $c->id)); ?>" class="btn btn-sm btn-outline-telkom" style="font-size:11.5px; padding:3px 8px; white-space:nowrap;" title="Lihat Detail Profil Pelanggan" data-bs-toggle="tooltip">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
    <?php if($customers->count() >= 20): ?>
    <div style="padding:12px 20px; border-top:1px solid var(--border); text-align:center;">
        <a href="<?php echo e(url('/customers?search=' . urlencode($q))); ?>" style="font-size:12.5px; color:var(--primary); font-weight:600; text-decoration:none;">
            Lihat semua pelanggan dengan kata kunci ini <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>


<?php if($visits->count() > 0): ?>
<div class="card p-0 mb-4" style="overflow:hidden;">
    <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:700; font-size:14px; color:var(--ink-900);">
            <i class="bi bi-geo-alt-fill" style="color:#3B82F6; margin-right:6px;"></i>
            Kunjungan Visit Lapangan
        </div>
        <span class="badge-status" style="background:#EFF6FF; color:#1D4ED8;"><?php echo e($visits->count()); ?> hasil</span>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>No Internet</th>
                    <th>Hasil Visit</th>
                    <th>AR Agent</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $visits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td style="font-size:12px; white-space:nowrap;"><?php echo e($v->tanggal_input ? $v->tanggal_input->format('d/m/Y') : '-'); ?></td>
                    <td style="font-weight:600; color:var(--ink-900);"><?php echo e($v->nama_pelanggan ?: optional($v->customer)->nama_pelanggan ?: '-'); ?></td>
                    <td><code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px;"><?php echo e($v->nomor_internet); ?></code></td>
                    <td>
                        <span class="badge-status <?php echo e($v->is_ptp ? 'badge-ptp' : 'badge-not-contacted'); ?>" style="font-size:11px;">
                            <?php echo e($v->hasil_visit); ?>

                        </span>
                    </td>
                    <td style="font-size:12px; color:var(--ink-500);"><?php echo e(optional($v->arAgent)->name ?: '-'); ?></td>
                    <td style="text-align:center;">
                        <a href="<?php echo e(url('/visits/' . $v->id)); ?>" class="btn btn-sm" style="background:#EFF6FF; color:#1E40AF; font-weight:600; font-size:11px; padding:3px 8px; border-radius:6px; text-decoration:none;">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<?php if($caringLogs->count() > 0): ?>
<div class="card p-0 mb-4" style="overflow:hidden;">
    <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:700; font-size:14px; color:var(--ink-900);">
            <i class="bi bi-telephone-fill" style="color:var(--success); margin-right:6px;"></i>
            Hasil Caring OBC
        </div>
        <span class="badge-status badge-contacted"><?php echo e($caringLogs->count()); ?> hasil</span>
    </div>
    <div class="table-responsive">
        <table class="table-modern mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>No Internet</th>
                    <th>Status</th>
                    <th>VOC</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $caringLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td style="font-size:12px; white-space:nowrap;"><?php echo e($log->tanggal_caring ? $log->tanggal_caring->format('d/m/Y') : '-'); ?></td>
                    <td style="font-weight:600; color:var(--ink-900);"><?php echo e($log->nama_pelanggan); ?></td>
                    <td><code style="background:var(--secondary); padding:2px 6px; border-radius:5px; font-size:12px;"><?php echo e($log->nomor_internet); ?></code></td>
                    <td>
                        <?php if($log->status_caring === 'CONTACTED'): ?>
                            <span class="badge-status badge-contacted"><i class="bi bi-check-circle"></i> Contacted</span>
                        <?php else: ?>
                            <span class="badge-status badge-not-contacted"><i class="bi bi-x-circle"></i> Uncontacted</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;"><?php echo e($log->voc ?: '-'); ?></td>
                    <td style="font-size:12px; color:var(--ink-500);"><?php echo e($log->petugas_caring ?: '-'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<?php if($arAgents->count() > 0): ?>
<div class="card mb-4">
    <div style="font-weight:700; font-size:14px; color:var(--ink-900); margin-bottom:14px;">
        <i class="bi bi-person-badge-fill" style="color:var(--warning); margin-right:6px;"></i>
        AR Agent
    </div>
    <div class="d-flex flex-wrap gap-3">
        <?php $__currentLoopData = $arAgents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="padding:14px 18px; background:var(--secondary); border:1px solid var(--border); border-radius:12px; min-width:180px;">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-circle"><?php echo e(strtoupper(substr($agent->name, 0, 2))); ?></div>
                <div>
                    <div style="font-weight:700; font-size:13px; color:var(--ink-900);"><?php echo e($agent->name); ?></div>
                    <div style="font-size:11.5px; color:var(--ink-400);"><?php echo e($agent->visits_count); ?> Visit</div>
                </div>
            </div>
            <a href="<?php echo e(url('/ar-agents')); ?>" style="font-size:11px; color:var(--primary); text-decoration:none; display:block; margin-top:8px; font-weight:600;">
                Lihat Profil <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/search/results.blade.php ENDPATH**/ ?>