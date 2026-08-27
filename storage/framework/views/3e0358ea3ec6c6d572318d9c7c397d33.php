<?php $__env->startSection('title', $customer->nama_pelanggan ?: 'Customer Profile 360'); ?>
<?php $__env->startSection('subtitle', 'Customer 360 Intelligence — No. Internet: ' . $customer->nomor_internet); ?>

<?php $__env->startSection('content'); ?>


<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?php echo e(url('/customers')); ?>" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Customer
    </a>
    <div class="d-flex gap-2">
        <a href="<?php echo e(url('/visits?search=' . $customer->nomor_internet)); ?>" class="btn btn-sm" style="background:var(--primary-soft); color:var(--primary-dark); font-weight:600; border-radius:8px;">
            <i class="bi bi-geo-alt"></i> Lihat Semua Visit
        </a>
        <a href="<?php echo e(url('/c3mr/hasil-caring?search=' . $customer->nomor_internet)); ?>" class="btn btn-sm" style="background:#EFF6FF; color:#2563EB; font-weight:600; border-radius:8px;">
            <i class="bi bi-telephone"></i> Riwayat Caring
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    
    
    
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="section-title">
                    <i class="bi bi-person-vcard-fill" style="color:var(--primary); margin-right:6px;"></i>
                    Identitas Pelanggan (Customer Master)
                </div>
                <span class="badge" style="background:var(--secondary); color:var(--ink-700); font-size:12px; font-weight:600; padding:4px 8px; border-radius:6px;">
                    NCLI: <?php echo e($customer->ncli ?: '-'); ?>

                </span>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 g-3">
                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">No. Internet (SND)</div>
                    <div style="font-size:14.5px; font-weight:700; color:var(--ink-900);">
                        <code><?php echo e($customer->nomor_internet); ?></code>
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Nama Pelanggan</div>
                    <div style="font-size:14.5px; font-weight:700; color:var(--ink-900);">
                        <?php echo e($customer->nama_pelanggan ?: '-'); ?>

                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">No. Handphone (Update C3MR)</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-900); display:flex; align-items:center; gap:8px; margin-top:2px;">
                        <?php if($customer->wa_url): ?>
                            <a href="<?php echo e($customer->wa_url); ?>" target="_blank" class="btn btn-sm btn-success text-white" style="background:#16A34A; border:none; font-size:12px; font-weight:600; padding:3px 10px; border-radius:7px; display:inline-flex; align-items:center; gap:5px;" title="Kirim Pesan WhatsApp Otomatis" data-bs-toggle="tooltip">
                                <i class="bi bi-whatsapp"></i> <?php echo e($customer->no_hp_terbaru); ?>

                            </a>
                        <?php elseif($customer->no_hp_terbaru): ?>
                            <span><?php echo e($customer->no_hp_terbaru); ?></span>
                        <?php else: ?>
                            <span class="text-muted">(Belum tersedia)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Email</div>
                    <div style="font-size:13px; color:var(--ink-700);">
                        <?php echo e($customer->email ?: '-'); ?>

                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Layanan Produk</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-700);">
                        <?php echo e($customer->nama_layanan_internet ?: 'IndiHome / Internet'); ?>

                    </div>
                </div>

                <div>
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Wilayah (Datel / STO)</div>
                    <div style="font-size:13.5px; font-weight:600; color:var(--ink-700);">
                        <?php echo e($customer->datel ?: ($customer->sto ?: '-')); ?>

                    </div>
                </div>

                <div class="col-sm-12">
                    <div style="font-size:11px; color:var(--ink-400); font-weight:700; text-transform:uppercase;">Alamat Pemasangan</div>
                    <div style="font-size:13px; color:var(--ink-700); line-height:1.5;">
                        <?php echo e($customer->alamat ?: '-'); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    
    
    
    <div class="col-lg-5">
        <div class="card h-100" style="border-left:4px solid <?php echo e($churnEval['level'] === 'CRITICAL' ? '#991B1B' : ($churnEval['level'] === 'HIGH' ? 'var(--danger)' : 'var(--primary)')); ?>;">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="section-title">
                    <i class="bi bi-shield-shaded" style="color:var(--primary); margin-right:6px;"></i>
                    Collection &amp; Churn Risk
                </div>
                <?php if($churnEval['level'] === 'CRITICAL'): ?>
                    <span class="badge" style="background:#450A0A; color:#fff; font-size:11px; padding:4px 8px; border-radius:99px;">CRITICAL RISK (<?php echo e($churnEval['score']); ?>)</span>
                <?php elseif($churnEval['level'] === 'HIGH'): ?>
                    <span class="badge-status badge-risk-high">HIGH RISK (<?php echo e($churnEval['score']); ?>)</span>
                <?php elseif($churnEval['level'] === 'MEDIUM'): ?>
                    <span class="badge-status badge-risk-medium">MEDIUM RISK (<?php echo e($churnEval['score']); ?>)</span>
                <?php else: ?>
                    <span class="badge-status badge-risk-low">LOW RISK (<?php echo e($churnEval['score']); ?>)</span>
                <?php endif; ?>
            </div>

            
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div style="background:var(--secondary); padding:10px 12px; border-radius:10px;">
                        <div style="font-size:11px; color:var(--ink-400); font-weight:600;">Saldo Piutang</div>
                        <div style="font-size:16px; font-weight:800; color:var(--danger); margin-top:2px; white-space:nowrap;">
                            Rp <?php echo e(number_format($customer->saldo_piutang, 0, ',', '.')); ?>

                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:var(--secondary); padding:10px 12px; border-radius:10px;">
                        <div style="font-size:11px; color:var(--ink-400); font-weight:600;">Umur Tunggakan</div>
                        <div style="font-size:14px; font-weight:700; color:var(--ink-900); margin-top:2px;">
                            <?php echo e($customer->umur_customer ?: '-'); ?>

                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="background:var(--secondary); padding:8px 10px; border-radius:8px; text-align:center;">
                        <div style="font-size:10.5px; color:var(--ink-400);">Total Visit</div>
                        <div style="font-size:14px; font-weight:700; color:var(--ink-900);"><?php echo e($customer->visits->count()); ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="background:var(--secondary); padding:8px 10px; border-radius:8px; text-align:center;">
                        <div style="font-size:10.5px; color:var(--ink-400);">Total PTP</div>
                        <div style="font-size:14px; font-weight:700; color:var(--warning);"><?php echo e($customer->visits->where('is_ptp', true)->count()); ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="background:var(--secondary); padding:8px 10px; border-radius:8px; text-align:center;">
                        <div style="font-size:10.5px; color:var(--ink-400);">Total Caring</div>
                        <div style="font-size:14px; font-weight:700; color:var(--success);"><?php echo e($customer->caringLogs->count()); ?></div>
                    </div>
                </div>
            </div>

            
            <div style="background:var(--primary-soft); padding:12px; border-radius:10px; border:1px solid rgba(226,0,26,0.15); margin-bottom:12px;">
                <div style="font-size:11px; font-weight:700; color:var(--primary-dark); text-transform:uppercase; margin-bottom:4px;">
                    <i class="bi bi-lightbulb-fill"></i> Rekomendasi Tindakan Collection
                </div>
                <div style="font-size:13px; font-weight:600; color:var(--ink-900); line-height:1.4;">
                    <?php echo e($churnEval['recommendation']); ?>

                </div>
            </div>

            
            <?php if(!empty($churnEval['reasons'])): ?>
                <div class="mb-3">
                    <div style="font-size:11px; font-weight:700; color:var(--ink-400); text-transform:uppercase; margin-bottom:4px;">Faktor Indikator Risiko:</div>
                    <ul style="margin:0; padding-left:16px; font-size:11.5px; color:var(--ink-700);">
                        <?php $__currentLoopData = $churnEval['reasons']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($r); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            
            <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:12px; padding:14px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px; font-weight:700; color:#166534;">
                        <i class="bi bi-whatsapp"></i> Template Pesan WhatsApp Resmi
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="copyWaText()" style="font-size:11px; padding:2px 8px; border-radius:6px;">
                        <i class="bi bi-clipboard"></i> Salin Teks
                    </button>
                </div>
                <div style="font-size:11.5px; color:#14532D; background:#FFFFFF; border:1px solid #DCFCE7; border-radius:8px; padding:10px; max-height:140px; overflow-y:auto; font-family:inherit; white-space:pre-wrap; line-height:1.4;" id="waTemplateText"><?php echo e($customer->wa_message_template); ?></div>
                <?php if($customer->wa_url): ?>
                    <div class="mt-2">
                        <a href="<?php echo e($customer->wa_url); ?>" target="_blank" class="btn btn-sm btn-success w-100 d-flex align-items-center justify-content-center gap-2" style="background:#16A34A; border:none; font-weight:700; font-size:12.5px; padding:7px 12px; border-radius:8px;">
                            <i class="bi bi-whatsapp"></i> Kirim Pesan WhatsApp ke Pelanggan
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mt-2 text-center text-muted" style="font-size:11px;">
                        <i class="bi bi-exclamation-circle"></i> Nomor HP belum tersedia untuk pengiriman otomatis via WhatsApp.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>




<div class="card p-0" style="overflow:hidden;">
    <div style="padding:16px 20px; border-bottom:1px solid var(--border);">
        <ul class="nav nav-pills" id="customerTab" role="tablist" style="gap:8px;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="visit-tab" data-bs-toggle="pill" data-bs-target="#visitTabContent" type="button" role="tab" style="font-size:13px; font-weight:600; border-radius:8px;">
                    <i class="bi bi-geo-alt-fill"></i> Riwayat Kunjungan Visit (<?php echo e($customer->visits->count()); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="caring-tab" data-bs-toggle="pill" data-bs-target="#caringTabContent" type="button" role="tab" style="font-size:13px; font-weight:600; border-radius:8px;">
                    <i class="bi bi-telephone-fill"></i> Riwayat Caring OBC (<?php echo e($customer->caringLogs->count()); ?>)
                </button>
            </li>
            <?php if($customer->viseeproData->count() > 0): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="viseepro-tab" data-bs-toggle="pill" data-bs-target="#viseeproTabContent" type="button" role="tab" style="font-size:13px; font-weight:600; border-radius:8px;">
                        <i class="bi bi-clipboard-data-fill"></i> Data Viseepro (<?php echo e($customer->viseeproData->count()); ?>)
                    </button>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="tab-content p-3" id="customerTabContent">
        
        <div class="tab-pane fade show active" id="visitTabContent" role="tabpanel">
            <?php $__empty_1 = true; $__currentLoopData = $customer->visits->sortByDesc('tanggal_input'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $photoUrl = route('visit.photo', ['visit' => $visit->id]);
                ?>
                <div class="timeline-item d-flex gap-3 py-3" style="border-bottom:1px solid var(--border);">
                    
                    <div style="flex-shrink:0;">
                        <?php if($visit->drive_file_id): ?>
                            <button type="button" class="btn p-0 border-0 bg-transparent"
                                    data-bs-toggle="modal" data-bs-target="#photoModal<?php echo e($visit->id); ?>">
                                <img src="<?php echo e($photoUrl); ?>"
                                     class="photo-thumb"
                                     alt="Foto visit"
                                     loading="lazy"
                                     style="width:56px; height:56px; object-fit:cover; border-radius:10px; border:1px solid var(--border); cursor:pointer;"
                                     onerror="this.src='<?php echo e(asset('images/photo-placeholder.svg')); ?>'">
                            </button>

                            
                            <div class="modal fade" id="photoModal<?php echo e($visit->id); ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content" style="border-radius:16px; overflow:hidden;">
                                        <div class="modal-header">
                                            <h6 class="modal-title font-weight-bold">
                                                Foto Visit — <?php echo e($customer->nama_pelanggan); ?> (<?php echo e($visit->tanggal_input?->format('d/m/Y')); ?>)
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-center p-3" style="background:#0B0F19;">
                                            <img src="<?php echo e($photoUrl); ?>"
                                                 alt="Foto visit <?php echo e($customer->nama_pelanggan); ?>"
                                                 style="max-width:100%; max-height:70vh; object-fit:contain; border-radius:8px;"
                                                 onerror="this.src='<?php echo e(asset('images/photo-placeholder.svg')); ?>'">
                                        </div>
                                        <div class="modal-footer">
                                            <?php if($visit->drive_url): ?>
                                                <a href="<?php echo e($visit->drive_url); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm">
                                                    <i class="bi bi-google"></i> Buka di Google Drive
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="photo-placeholder" style="width:56px; height:56px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:var(--secondary); color:var(--ink-400);">
                                <i class="bi bi-image" style="font-size:20px;"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span style="font-weight:700; font-size:14px; color:var(--ink-900);">
                                    <?php echo e($visit->hasil_visit ?: 'Belum Diisi'); ?>

                                </span>
                                <?php if($visit->is_ptp): ?>
                                    <span class="badge-status badge-ptp ms-2">
                                        <i class="bi bi-cash-coin"></i> Janji Bayar (PTP)
                                    </span>
                                <?php endif; ?>
                                <?php if($visit->kategori_visit && $visit->kategori_visit !== '-'): ?>
                                    <span class="badge" style="background:var(--secondary); color:var(--ink-700); font-size:11px; margin-left:4px;">
                                        <?php echo e($visit->kategori_visit); ?>

                                    </span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size:12px; color:var(--ink-400);">
                                <?php echo e($visit->tanggal_input ? $visit->tanggal_input->translatedFormat('d F Y') : '-'); ?>

                            </span>
                        </div>

                        <div style="font-size:12.5px; color:var(--ink-500); margin-top:4px;">
                            <i class="bi bi-person-badge"></i> AR Agent: <strong><?php echo e(optional($visit->arAgent)->name ?? '-'); ?></strong>
                            <?php if($visit->keterangan_visit && $visit->keterangan_visit !== '-'): ?>
                                &middot; <span><?php echo e($visit->keterangan_visit); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="empty-state py-4">
                    <i class="bi bi-clock-history"></i> Belum ada riwayat visit untuk pelanggan ini.
                </div>
            <?php endif; ?>
        </div>

        
        <div class="tab-pane fade" id="caringTabContent" role="tabpanel">
            <?php $__empty_1 = true; $__currentLoopData = $customer->caringLogs->sortByDesc('tanggal_caring'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caring): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="d-flex gap-3 py-3" style="border-bottom:1px solid var(--border);">
                    <div class="avatar-circle" style="background:#EFF6FF; color:#2563EB; width:44px; height:44px; font-size:18px;">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span style="font-weight:700; font-size:14px; color:var(--ink-900);">
                                    <?php echo e($caring->voc ?: 'General Caring'); ?>

                                </span>
                                <?php if($caring->status_caring === 'CONTACTED'): ?>
                                    <span class="badge-status badge-contacted ms-2">Contacted</span>
                                <?php else: ?>
                                    <span class="badge-status badge-not-contacted ms-2">Uncontacted</span>
                                <?php endif; ?>
                                <?php if($caring->status_bayar === 'PAID'): ?>
                                    <span class="badge-status ms-1" style="background:#D1FAE5; color:#059669;">PAID</span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size:12px; color:var(--ink-400);">
                                <?php echo e($caring->tanggal_caring ? $caring->tanggal_caring->format('d M Y') : '-'); ?>

                            </span>
                        </div>
                        <div style="font-size:12.5px; color:var(--ink-500); margin-top:4px;">
                            Petugas: <strong><?php echo e($caring->petugas_caring ?: '-'); ?></strong>
                            <?php if($caring->keterangan): ?>
                                &middot; <span><?php echo e($caring->keterangan); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="empty-state py-4">
                    <i class="bi bi-telephone-x"></i> Belum ada data riwayat caring untuk pelanggan ini.
                </div>
            <?php endif; ?>
        </div>

        
        <?php if($customer->viseeproData->count() > 0): ?>
            <div class="tab-pane fade" id="viseeproTabContent" role="tabpanel">
                <?php $__currentLoopData = $customer->viseeproData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-3 mb-2" style="background:var(--secondary); border-radius:10px;">
                        <div class="d-flex justify-content-between">
                            <div style="font-weight:700; color:var(--ink-900);">Activity #<?php echo e($vp->activity_id); ?> — <?php echo e($vp->nama_perusahaan ?: $customer->nama_pelanggan); ?></div>
                            <span class="badge" style="background:var(--surface); color:var(--ink-700); border:1px solid var(--border);"><?php echo e($vp->sto); ?> (<?php echo e($vp->witel); ?>)</span>
                        </div>
                        <div class="row g-2 mt-2" style="font-size:12px; color:var(--ink-700);">
                            <div class="col-sm-6">Agent: <strong><?php echo e($vp->nama_agent); ?></strong></div>
                            <div class="col-sm-6">PIC: <strong><?php echo e($vp->pic_name ?: '-'); ?></strong> (<?php echo e($vp->pic_cp ?: '-'); ?>)</div>
                            <div class="col-sm-12">Alamat: <?php echo e($vp->address ?: '-'); ?></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function copyWaText() {
    const textEl = document.getElementById('waTemplateText');
    if (!textEl) return;
    navigator.clipboard.writeText(textEl.innerText).then(() => {
        alert('Teks pesan WhatsApp berhasil disalin ke clipboard!');
    }).catch(err => {
        console.error('Gagal menyalin teks: ', err);
    });
}
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/customers/show.blade.php ENDPATH**/ ?>