<!DOCTYPE html>
<html>
<head>
    <title>PTP Monitoring</title>

    <style>

        body{
            font-family:Arial,sans-serif;
            background:#f5f7fb;
            padding:30px;
        }

        table{
            width:100%;
            background:white;
            border-collapse:collapse;
        }

        th{
            background:#E30613;
            color:white;
            padding:12px;
            text-align:left;
        }

        td{
            padding:12px;
            border-bottom:1px solid #ddd;
        }

    </style>

</head>
<body>

<h1>Promise To Pay (PTP)</h1>

<table>

<thead>

<tr>
    <th>Tanggal</th>
    <th>Customer</th>
    <th>AR Agent</th>
    <th>Keterangan</th>
</tr>

</thead>

<tbody>

<?php $__currentLoopData = $ptps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ptp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

<tr>

    <td>
        <?php echo e($ptp->tanggal_input); ?>

    </td>

    <td>
        <?php echo e($ptp->customer->nama_layanan_internet ?? '-'); ?>

    </td>

    <td>
        <?php echo e($ptp->arAgent->name ?? '-'); ?>

    </td>

    <td>
        <?php echo e($ptp->keterangan_visit); ?>

    </td>

</tr>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

</tbody>

</table>

<br>

<?php echo e($ptps->links()); ?>


</body>
</html><?php /**PATH D:\Projek Telkom Reva\ptp-intelligence-dashboard\resources\views/ptp/index.blade.php ENDPATH**/ ?>