<?php $__env->startSection('title', 'Sync Data C3MR'); ?>
<?php $__env->startSection('subtitle', 'Pusat integrasi satu pintu data Google Spreadsheet C3MR, Report PRQ, dan VISEEPRO'); ?>

<?php $__env->startPush('styles'); ?>
<style>
.sync-hero-card {
    background: linear-gradient(135deg, #FFFFFF 0%, #FFF5F5 100%);
    border: 1px solid rgba(226, 0, 26, 0.18);
    border-radius: 16px;
    padding: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(226, 0, 26, 0.06);
}
.sync-hero-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #E2001A, #FF3B4E, #F59E0B);
}
.btn-sync-hero {
    background: linear-gradient(135deg, #E2001A 0%, #B8000F 100%);
    color: #FFFFFF !important;
    border: none;
    border-radius: 12px;
    padding: 14px 32px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: .02em;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 6px 20px rgba(226, 0, 26, 0.28);
    transition: all .2s ease;
    cursor: pointer;
    min-width: 240px;
}
.btn-sync-hero:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(226, 0, 26, 0.38);
    filter: brightness(1.05);
}
.btn-sync-hero:active:not(:disabled) {
    transform: translateY(0);
}
.btn-sync-hero:disabled {
    opacity: 0.85;
    cursor: not-allowed;
    filter: grayscale(0.2);
}
.sync-icon.spinning {
    animation: spinSync 1s linear infinite;
    display: inline-block;
}
@keyframes spinSync {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.sync-result-box {
    border-radius: 14px;
    background: #FFFFFF;
    border: 1px solid var(--border);
    padding: 20px;
    transition: all .2s ease;
}
.source-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
}
.source-badge-success { background: #DCFCE7; color: #166534; }
.source-badge-error { background: #FEE2E2; color: #991B1B; }
.sync-grid-item {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 14px 16px;
    transition: transform .15s ease, border-color .15s ease;
}
.sync-grid-item:hover {
    border-color: #CBD5E1;
    background: #FFFFFF;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>


<?php if(session('success') && !session('syncResult')): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
        <i class="bi bi-check-circle-fill"></i> <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>

<?php if(session('error') && !session('syncResult')): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>


<div class="card sync-hero-card mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge" style="background:#FDEBEC; color:#B8000F; font-weight:700; font-size:11px; padding:5px 10px; border-radius:6px;">
                    <i class="bi bi-lightning-charge-fill"></i> SINKRONISASI SATU PINTU
                </span>
                <span class="badge" style="background:#EFF6FF; color:#2563EB; font-weight:600; font-size:11px; padding:5px 10px; border-radius:6px;" id="lastSyncBadge">
                    <i class="bi bi-clock-history"></i> Last Sync: <span id="lastSyncText"><?php echo e($lastSyncFormatted); ?></span>
                </span>
            </div>
            <h4 style="font-weight:800; color:var(--ink-900); margin-bottom:6px; font-size:22px;">
                Sync Data C3MR
            </h4>
            <p style="color:var(--ink-500); font-size:13.5px; margin-bottom:0; line-height:1.5;">
                Pusat pembaruan seluruh sumber data aplikasi. Memperbarui otomatis <strong>Report PRQ</strong>, <strong>VISEEPRO</strong>, <strong>DATA ALL</strong>, <strong>Hasil Caring OBC</strong>, dan <strong>Performansi Witel Regional</strong> dalam satu kali klik.
            </p>
        </div>
        <div class="col-lg-5 text-lg-end text-start">
            <button type="button" id="btnSyncMaster" class="btn-sync-hero" onclick="triggerMasterSync()">
                <i class="bi bi-arrow-repeat sync-icon fs-5" id="masterSyncIcon"></i>
                <span id="masterSyncLabel">Sync Data C3MR</span>
            </button>
            <div style="font-size:12px; color:var(--ink-500); margin-top:8px; font-weight:500;">
                <i class="bi bi-info-circle"></i> Memperbarui data Report PRQ, VISEEPRO, dan C3MR
            </div>
        </div>
    </div>

    
    <div id="syncLiveStatus" class="mt-4 pt-3" style="display:none; border-top:1px dashed #FCA5A5;">
        <div class="d-flex align-items-center gap-3">
            <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
            <div>
                <strong style="color:var(--primary); font-size:13.5px;">Sinkronisasi sedang berlangsung...</strong>
                <div style="font-size:12px; color:var(--ink-500);">Mengunduh data terbaru dari Google Spreadsheet dan memperbarui database. Mohon tunggu...</div>
            </div>
        </div>
    </div>
</div>


<div id="syncResultContainer" class="card mb-4" style="<?php echo e(($lastSyncResult || session('syncResult')) ? '' : 'display:none;'); ?>">
    <?php
        $activeResult = session('syncResult') ?: $lastSyncResult;
        $statusKey = $activeResult['status'] ?? ($lastSyncStatus ?: 'success');
        $statusLabel = $activeResult['status_label'] ?? ($statusKey === 'success' ? 'Sinkronisasi berhasil' : 'Sinkronisasi selesai dengan beberapa masalah');
        $details = $activeResult['details'] ?? [];
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom:1px solid var(--border);">
        <div class="d-flex align-items-center gap-2">
            <span id="resultStatusIcon">
                <?php if($statusKey === 'success'): ?>
                    <i class="bi bi-check-circle-fill fs-5" style="color:var(--success);"></i>
                <?php elseif($statusKey === 'warning'): ?>
                    <i class="bi bi-exclamation-triangle-fill fs-5" style="color:var(--warning);"></i>
                <?php else: ?>
                    <i class="bi bi-x-circle-fill fs-5" style="color:var(--danger);"></i>
                <?php endif; ?>
            </span>
            <div>
                <div class="section-title mb-0" id="resultStatusTitle">
                    <?php echo e($statusKey === 'success' ? '✓ ' : ($statusKey === 'warning' ? '⚠ ' : '✕ ')); ?> <?php echo e($statusLabel); ?>

                </div>
                <div class="section-sub" id="resultStatusSub">
                    Terakhir diperbarui: <span id="resultTimestampText"><?php echo e($lastSyncFormatted); ?></span>
                </div>
            </div>
        </div>
        <div>
            <span class="badge" id="resultSummaryBadge" style="background:var(--secondary); color:var(--ink-700); font-weight:600; font-size:11.5px; padding:6px 12px; border-radius:8px;">
                <span id="resultProcessedCount"><?php echo e(number_format($activeResult['total_rows_processed'] ?? ($totalVisits + $totalCustomers + $totalCaring))); ?></span> records diproses
            </span>
        </div>
    </div>

    
    <div class="row g-3" id="syncDetailsGrid">
        
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-geo-alt-fill" style="color:var(--primary); margin-right:4px;"></i> Report PRQ
                    </div>
                    <span class="source-badge <?php echo e(($details['report_prq']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error'); ?>" id="badge_report_prq">
                        <?php echo e(($details['report_prq']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal'); ?>

                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_report_prq">
                    <?php echo e(number_format($details['report_prq']['count'] ?? $totalVisits)); ?> <span style="font-size:12px; font-weight:500; color:var(--ink-500);">records</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_report_prq">
                    <?php echo e($details['report_prq']['message'] ?? 'Data kunjungan lapangan, PTP & nomor kontak valid'); ?>

                </div>
            </div>
        </div>

        
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-building-check" style="color:#2563EB; margin-right:4px;"></i> VISEEPRO
                    </div>
                    <span class="source-badge <?php echo e(($details['viseepro']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error'); ?>" id="badge_viseepro">
                        <?php echo e(($details['viseepro']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal'); ?>

                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_viseepro">
                    <?php echo e(number_format($details['viseepro']['count'] ?? $totalViseepro)); ?> <span style="font-size:12px; font-weight:500; color:var(--ink-500);">records</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_viseepro">
                    <?php echo e($details['viseepro']['message'] ?? 'Aktivitas survey AR, profil perusahaan, PIC & koordinat'); ?>

                </div>
            </div>
        </div>

        
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-people-fill" style="color:#059669; margin-right:4px;"></i> C3MR Master Data
                    </div>
                    <span class="source-badge <?php echo e(($details['data_all']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error'); ?>" id="badge_data_all">
                        <?php echo e(($details['data_all']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal'); ?>

                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_data_all">
                    <?php echo e(number_format($details['data_all']['count'] ?? $totalCustomers)); ?> <span style="font-size:12px; font-weight:500; color:var(--ink-500);">pelanggan</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_data_all">
                    <?php echo e($details['data_all']['message'] ?? 'Master pelanggan, STO, Datel, No HP & Saldo Piutang'); ?>

                </div>
            </div>
        </div>

        
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-telephone-inbound-fill" style="color:#7C3AED; margin-right:4px;"></i> C3MR Hasil Caring
                    </div>
                    <span class="source-badge <?php echo e(($details['caring']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error'); ?>" id="badge_caring">
                        <?php echo e(($details['caring']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal'); ?>

                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_caring">
                    <?php echo e(number_format($details['caring']['count'] ?? $totalCaring)); ?> <span style="font-size:12px; font-weight:500; color:var(--ink-500);">log caring</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_caring">
                    <?php echo e($details['caring']['message'] ?? 'Riwayat penagihan telepon OBC PRITI, VOC & status bayar'); ?>

                </div>
            </div>
        </div>

        
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-diagram-3-fill" style="color:#D97706; margin-right:4px;"></i> Performansi Witel
                    </div>
                    <span class="source-badge <?php echo e(($details['performance']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error'); ?>" id="badge_performance">
                        <?php echo e(($details['performance']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal'); ?>

                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_performance">
                    <?php echo e(number_format($details['performance']['count'] ?? $totalWitel)); ?> <span style="font-size:12px; font-weight:500; color:var(--ink-500);">witel</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_performance">
                    <?php echo e($details['performance']['message'] ?? 'Rekapitulasi performansi Billing, Cash Collection & % CYC'); ?>

                </div>
            </div>
        </div>

        
        <div class="col-md-6 col-lg-4">
            <div class="sync-grid-item h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">
                        <i class="bi bi-person-badge-fill" style="color:#0284C7; margin-right:4px;"></i> AR Agents
                    </div>
                    <span class="source-badge <?php echo e(($details['ar_agents']['success'] ?? true) ? 'source-badge-success' : 'source-badge-error'); ?>" id="badge_ar_agents">
                        <?php echo e(($details['ar_agents']['success'] ?? true) ? '✓ Berhasil' : '✕ Gagal'); ?>

                    </span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--ink-900);" id="count_ar_agents">
                    <?php echo e(number_format($details['ar_agents']['count'] ?? 19)); ?> <span style="font-size:12px; font-weight:500; color:var(--ink-500);">agent unik</span>
                </div>
                <div style="font-size:11.5px; color:var(--ink-500); margin-top:4px; line-height:1.4;" id="msg_ar_agents">
                    <?php echo e($details['ar_agents']['message'] ?? 'Konsolidasi variasi penulisan nama agent petugas'); ?>

                </div>
            </div>
        </div>
    </div>
</div>


<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Master Pelanggan</div>
                <div class="kpi-value" id="kpiCustomers"><?php echo e(number_format($totalCustomers)); ?></div>
                <div style="font-size:11px; color:var(--success); margin-top:4px;">
                    <i class="bi bi-check-circle-fill"></i> <?php echo e(number_format($validPhones)); ?> No HP valid
                </div>
            </div>
            <div class="kpi-icon" style="background:var(--primary-soft); color:var(--primary);">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Riwayat Visit Lapangan</div>
                <div class="kpi-value" id="kpiVisits"><?php echo e(number_format($totalVisits)); ?></div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Sumber: Report PRQ</div>
            </div>
            <div class="kpi-icon" style="background:#EFF6FF; color:#2563EB;">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Log Caring Terkumpul</div>
                <div class="kpi-value" id="kpiCaring"><?php echo e(number_format($totalCaring)); ?></div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Aktivitas OBC PRITI</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-telephone-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Aktivitas VISEEPRO</div>
                <div class="kpi-value" id="kpiViseepro"><?php echo e(number_format($totalViseepro)); ?></div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Survey &amp; Profil PIC</div>
            </div>
            <div class="kpi-icon" style="background:var(--secondary); color:var(--ink-700);">
                <i class="bi bi-building-check"></i>
            </div>
        </div>
    </div>
</div>


<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="section-title mb-1"><i class="bi bi-sliders" style="color:var(--primary);"></i> Sinkronisasi Sumber Data Individual</div>
                    <div class="section-sub">Opsi manual jika hanya ingin menyegarkan sheet tertentu secara terpisah</div>
                </div>
            </div>

            <div class="row g-3">
                
                <div class="col-md-6">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">1. Sheet DATA ALL</div>
                            <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                                Melengkapi Master Customer, Nomor HP valid, Alamat, STO, Datel, dan Saldo Piutang.
                            </div>
                        </div>
                        <form method="POST" action="<?php echo e(url('/c3mr/sync/data-all')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-outline-telkom btn-sm w-100 justify-content-center">
                                <i class="bi bi-arrow-repeat"></i> Sync DATA ALL
                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="col-md-6">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">2. Sheet HASIL CARING</div>
                            <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                                Mengimpor riwayat respons telepon OBC PRITI (VOC, Status Caring, Status Bayar).
                            </div>
                        </div>
                        <form method="POST" action="<?php echo e(url('/c3mr/sync/caring')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm w-100 justify-content-center" style="background:#EFF6FF; color:#2563EB; font-weight:600; border-radius:8px;">
                                <i class="bi bi-telephone-inbound"></i> Sync Hasil Caring
                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="col-md-6">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">3. Sheet PERFORMANSI DETAIL</div>
                            <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                                Memperbarui rekap performansi Witel (Billing, Cash Coll, % CYC, % CR, Rank).
                            </div>
                        </div>
                        <form method="POST" action="<?php echo e(url('/c3mr/sync/performance')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm w-100 justify-content-center" style="background:var(--warning-soft); color:#92400E; font-weight:600; border-radius:8px;">
                                <i class="bi bi-graph-up-arrow"></i> Sync Performansi Witel
                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="col-md-6">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">4. Normalisasi AR Agent</div>
                            <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                                Menggabungkan nama variasi duplikat tanpa merusak relasi histori kunjungan.
                            </div>
                        </div>
                        <form method="POST" action="<?php echo e(url('/c3mr/sync/consolidate-ar')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm w-100 justify-content-center" style="background:var(--success-soft); color:var(--success); font-weight:600; border-radius:8px;">
                                <i class="bi bi-person-check"></i> Konsolidasi AR Agent
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="section-title mb-1"><i class="bi bi-shield-check" style="color:var(--success);"></i> Kualitas &amp; Integritas Data</div>
            <div class="section-sub mb-3">Pemeriksaan kualitas master data</div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">No. HP Terisi Valid</span>
                <span style="font-weight:700; color:var(--success);">
                    <?php echo e($totalCustomers > 0 ? round(($validPhones / $totalCustomers) * 100, 1) : 0); ?>%
                </span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">Integrasi Report PRQ</span>
                <span class="badge" style="background:#D1FAE5; color:#059669;">Terkoneksi</span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">Integrasi VISEEPRO</span>
                <span class="badge" style="background:#D1FAE5; color:#059669;">Terkoneksi</span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">Integrasi Sheet C3MR</span>
                <span class="badge" style="background:#D1FAE5; color:#059669;">Terkoneksi (gviz CSV)</span>
            </div>

            <div class="mt-3 p-3" style="background:var(--secondary); border-radius:10px; font-size:11.5px; color:var(--ink-500); line-height:1.5;">
                <i class="bi bi-info-circle-fill" style="color:var(--primary);"></i>
                <strong>Catatan Integrasi:</strong> Data Report PRQ yang diperbarui akan otomatis mempengaruhi metrik di <em>Dashboard</em>, <em>PTP Monitoring</em>, <em>Risk Score</em>, dan <em>Customer 360</em>.
            </div>

            <div class="mt-3">
                <a href="<?php echo e(url('/settings')); ?>" class="btn btn-outline-secondary btn-sm w-100" style="border-radius:8px; font-weight:600; font-size:12px;">
                    <i class="bi bi-gear-fill"></i> Konfigurasi Link Spreadsheet
                </a>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function triggerMasterSync() {
    const btn = document.getElementById('btnSyncMaster');
    const icon = document.getElementById('masterSyncIcon');
    const label = document.getElementById('masterSyncLabel');
    const liveStatus = document.getElementById('syncLiveStatus');
    const progressBar = document.getElementById('topProgressBar');

    // UI Loading State (mengikuti proses sebenarnya tanpa fake timer)
    btn.disabled = true;
    icon.classList.add('spinning');
    label.innerText = 'Syncing Data C3MR...';
    liveStatus.style.display = 'block';
    
    if (progressBar) {
        progressBar.className = 'loading';
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || '<?php echo e(csrf_token()); ?>';

    fetch('<?php echo e(route("c3mr.sync.all")); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({})
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Terjadi kesalahan pada server saat sinkronisasi');
        }
        return data;
    })
    .then(data => {
        // Update Timestamp Text
        if (data.last_sync_formatted) {
            document.getElementById('lastSyncText').innerText = data.last_sync_formatted;
            document.getElementById('resultTimestampText').innerText = data.last_sync_formatted;
        }

        // Update Status Box
        const statusBox = document.getElementById('syncResultContainer');
        statusBox.style.display = 'block';

        const isSuccess = data.status === 'success';
        const isWarning = data.status === 'warning';

        const statusIconEl = document.getElementById('resultStatusIcon');
        const statusTitleEl = document.getElementById('resultStatusTitle');

        if (isSuccess) {
            statusIconEl.innerHTML = '<i class="bi bi-check-circle-fill fs-5" style="color:var(--success);"></i>';
            statusTitleEl.innerText = '✓ ' + (data.status_label || 'Sinkronisasi berhasil');
        } else if (isWarning) {
            statusIconEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-5" style="color:var(--warning);"></i>';
            statusTitleEl.innerText = '⚠ ' + (data.status_label || 'Sinkronisasi selesai dengan beberapa masalah');
        } else {
            statusIconEl.innerHTML = '<i class="bi bi-x-circle-fill fs-5" style="color:var(--danger);"></i>';
            statusTitleEl.innerText = '✕ ' + (data.status_label || 'Sinkronisasi gagal');
        }

        if (data.total_rows_processed !== undefined) {
            document.getElementById('resultProcessedCount').innerText = Number(data.total_rows_processed).toLocaleString('id-ID');
        }

        // Update details per source
        if (data.details) {
            const d = data.details;

            // Report PRQ
            if (d.report_prq) {
                updateSourceCard('report_prq', d.report_prq);
            }
            // VISEEPRO
            if (d.viseepro) {
                updateSourceCard('viseepro', d.viseepro);
            }
            // DATA ALL
            if (d.data_all) {
                updateSourceCard('data_all', d.data_all);
            }
            // Caring
            if (d.caring) {
                updateSourceCard('caring', d.caring);
            }
            // Performance
            if (d.performance) {
                updateSourceCard('performance', d.performance);
            }
            // AR Agents
            if (d.ar_agents) {
                updateSourceCard('ar_agents', d.ar_agents);
            }
        }

        // Scroll to results smoothly
        statusBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    })
    .catch(err => {
        alert('Gagal melakukan sinkronisasi: ' + err.message);
    })
    .finally(() => {
        // Reset UI State
        btn.disabled = false;
        icon.classList.remove('spinning');
        label.innerText = 'Sync Data C3MR';
        liveStatus.style.display = 'none';
        
        if (progressBar) {
            progressBar.className = 'finish';
            setTimeout(() => { progressBar.className = ''; }, 300);
        }
    });
}

function updateSourceCard(key, item) {
    const badgeEl = document.getElementById('badge_' + key);
    const countEl = document.getElementById('count_' + key);
    const msgEl = document.getElementById('msg_' + key);

    if (badgeEl) {
        badgeEl.className = 'source-badge ' + (item.success ? 'source-badge-success' : 'source-badge-error');
        badgeEl.innerText = item.success ? '✓ Berhasil' : '✕ Gagal';
    }
    if (countEl && item.count !== undefined) {
        countEl.innerHTML = Number(item.count).toLocaleString('id-ID') + ' <span style="font-size:12px; font-weight:500; color:var(--ink-500);">records</span>';
    }
    if (msgEl && item.message) {
        msgEl.innerText = item.message;
    }
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/c3mr/sync.blade.php ENDPATH**/ ?>