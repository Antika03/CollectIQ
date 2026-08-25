<?php $__env->startSection('title', 'Settings'); ?>
<?php $__env->startSection('subtitle', 'Konfigurasi sumber data Google Sheet'); ?>

<?php $__env->startSection('content'); ?>

<div class="card" style="max-width:560px;">
    <div class="section-title mb-1"><i class="bi bi-gear-fill" style="color:var(--primary);"></i> Pengaturan Google Sheet</div>
    <div class="section-sub mb-4">URL sumber data untuk Report PRQ dan VISEEPRO</div>

    <form method="POST" action="/settings">
        <?php echo csrf_field(); ?>

        <div class="mb-3">
            <label class="form-label" style="font-size:13px; font-weight:600; color:var(--ink-700);">Link Report PRQ</label>
            <input type="text" name="report_prq_url" class="form-control" value="<?php echo e($setting->report_prq_url ?? ''); ?>">
        </div>

        <div class="mb-4">
            <label class="form-label" style="font-size:13px; font-weight:600; color:var(--ink-700);">Link VISEEPRO</label>
            <input type="text" name="viseepro_url" class="form-control" value="<?php echo e($setting->viseepro_url ?? ''); ?>">
        </div>

        <button type="submit" class="btn btn-primary-telkom">
            <i class="bi bi-save"></i> Simpan Setting
        </button>
    </form>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/settings/index.blade.php ENDPATH**/ ?>