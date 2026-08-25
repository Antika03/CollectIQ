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

@foreach($ptps as $ptp)

<tr>

    <td>
        {{ $ptp->tanggal_input }}
    </td>

    <td>
        {{ $ptp->customer->nama_layanan_internet ?? '-' }}
    </td>

    <td>
        {{ $ptp->arAgent->name ?? '-' }}
    </td>

    <td>
        {{ $ptp->keterangan_visit }}
    </td>

</tr>

@endforeach

</tbody>

</table>

<br>

{{ $ptps->links() }}

</body>
</html>