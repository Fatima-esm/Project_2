<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        .meta { color: #555; margin-bottom: 14px; }
        .box { border: 1px solid #ddd; padding: 8px 12px; display: inline-block; margin-right: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Period: {{ $period['from'] }} — {{ $period['to'] }}</div>

    <div class="box">Total Net: <b>{{ number_format($summary['total_net'], 2) }}</b></div>
    <div class="box">Paid: <b>{{ $summary['paid_count'] }}</b></div>
    <div class="box">Pending: <b>{{ $summary['pending_count'] }}</b></div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Role</th>
                <th>Membership</th>
                <th>Month</th>
                <th>Base</th>
                <th>Bonus</th>
                <th>Deduction</th>
                <th>Net</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr>
                <td>{{ $r['id'] }}</td>
                <td>{{ $r['employee'] }}</td>
                <td>{{ $r['role'] }}</td>
                <td>{{ $r['membership'] }}</td>
                <td>{{ $r['month'] }}</td>
                <td>{{ number_format($r['base'], 2) }}</td>
                <td>{{ number_format($r['bonus'], 2) }}</td>
                <td>{{ number_format($r['deduction'], 2) }}</td>
                <td>{{ number_format($r['net'], 2) }}</td>
                <td>{{ $r['status'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>