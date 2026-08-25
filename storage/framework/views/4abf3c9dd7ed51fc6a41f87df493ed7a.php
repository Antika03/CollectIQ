<?php $__env->startSection('title', 'C3MR Data Management & Sync'); ?>
<?php $__env->startSection('subtitle', 'Pusat integrasi data Google Spreadsheet C3MR, validasi kualitas data, dan sinkronisasi berkala'); ?>

<?php $__env->startSection('content'); ?>


<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Total Customer Terdaftar</div>
                <div class="kpi-value"><?php echo e(number_format($totalCustomers)); ?></div>
                <div style="font-size:11px; color:var(--success); margin-top:4px;">
                    <i class="bi bi-check-circle-fill"></i> <?php echo e(number_format($validPhones)); ?> memiliki No HP valid
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
                <div class="kpi-label">Log Caring Terkumpul</div>
                <div class="kpi-value"><?php echo e(number_format($totalCaring)); ?></div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Aktivitas OBC PRITI</div>
            </div>
            <div class="kpi-icon" style="background:#EFF6FF; color:#3B82F6;">
                <i class="bi bi-telephone-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Rekapitulasi Witel</div>
                <div class="kpi-value"><?php echo e(number_format($totalWitel)); ?></div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">CYC / C3MR regional</div>
            </div>
            <div class="kpi-icon" style="background:var(--warning-soft); color:var(--warning);">
                <i class="bi bi-diagram-3-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card kpi-card h-100">
            <div>
                <div class="kpi-label">Riwayat Visit Lapangan</div>
                <div class="kpi-value"><?php echo e(number_format($totalVisits)); ?></div>
                <div style="font-size:11px; color:var(--ink-400); margin-top:4px;">Bukti foto &amp; PTP</div>
            </div>
            <div class="kpi-icon" style="background:var(--secondary); color:var(--ink-700);">
                <i class="bi bi-geo-alt-fill"></i>
            </div>
        </div>
    </div>
</div>


<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="section-title mb-1"><i class="bi bi-cloud-arrow-down-fill" style="color:var(--primary);"></i> Sinkronisasi On-Demand dari Google Spreadsheet C3MR</div>
            <div class="section-sub mb-3">Sumber: <code>C3MR POTS PRITI AGUSTUS 2026 (NEW)</code></div>

            <div class="row g-3">
                
                <div class="col-md-6">
                    <div class="p-3" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">1. Sheet DATA ALL</div>
                        <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                            Melengkapi Master Customer, Nomor HP valid, Alamat, STO, Datel, dan Saldo Piutang.
                        </div>
                        <form method="POST" action="<?php echo e(url('/c3mr/sync/data-all')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-primary-telkom btn-sm w-100">
                                <i class="bi bi-arrow-repeat"></i> Sinkronisasi DATA ALL
                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="col-md-6">
                    <div class="p-3" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">2. Sheet HASIL CARING</div>
                        <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                            Mengimpor riwayat respons telepon (VOC, Status Caring, Status Bayar, Petugas).
                        </div>
                        <form method="POST" action="<?php echo e(url('/c3mr/sync/caring')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm w-100" style="background:#EFF6FF; color:#2563EB; font-weight:600; border-radius:8px;">
                                <i class="bi bi-telephone-inbound"></i> Sinkronisasi Hasil Caring
                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="col-md-6">
                    <div class="p-3" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">3. Sheet PERFORMANSI DETAIL</div>
                        <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                            Memperbarui rekap performansi Witel (Billing, Cash Coll, % CYC, % CR, Rank).
                        </div>
                        <form method="POST" action="<?php echo e(url('/c3mr/sync/performance')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm w-100" style="background:var(--warning-soft); color:#92400E; font-weight:600; border-radius:8px;">
                                <i class="bi bi-graph-up-arrow"></i> Sinkronisasi Performansi Witel
                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="col-md-6">
                    <div class="p-3" style="background:var(--secondary); border-radius:12px; border:1px solid var(--border);">
                        <div style="font-weight:700; font-size:13.5px; color:var(--ink-900);">4. Normalisasi AR Agent</div>
                        <div style="font-size:12px; color:var(--ink-500); margin:4px 0 12px; line-height:1.4;">
                            Menggabungkan nama variasi duplikat tanpa merusak relasi histori kunjungan.
                        </div>
                        <form method="POST" action="<?php echo e(url('/c3mr/sync/consolidate-ar')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-sm w-100" style="background:var(--success-soft); color:var(--success); font-weight:600; border-radius:8px;">
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
                <span style="font-size:12.5px; color:var(--ink-700);">Integrasi Spreadsheet</span>
                <span class="badge" style="background:#D1FAE5; color:#059669;">Tersambung (gviz CSV)</span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);">
                <span style="font-size:12.5px; color:var(--ink-700);">Metode Sinkronisasi</span>
                <span style="font-size:12px; font-weight:600; color:var(--ink-900);">On-Demand (Aman)</span>
            </div>

            <div class="mt-3 p-3" style="background:var(--secondary); border-radius:10px; font-size:11.5px; color:var(--ink-500); line-height:1.5;">
                <i class="bi bi-info-circle-fill" style="color:var(--primary);"></i>
                <strong>Catatan Keamanan:</strong> ID Google Spreadsheet dikelola terenkripsi dan sinkronisasi hanya dieksekusi saat tombol diklik tanpa memberatkan server.
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/c3mr/sync.blade.php ENDPATH**/ ?>