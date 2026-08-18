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

    <div class="box">Renewals: <b>{{ $summary['count'] }}</b></div>
    <div class="box">Revenue: <b>{{ number_format($summary['revenue'], 2) }}</b></div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Trainee</th>
                <th>Membership</th>
                <th>Plan</th>
                <th>Price</th>
                <th>Starts At</th>
                <th>Expires At</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr>
                <td>{{ $r['id'] }}</td>
                <td>{{ $r['trainee'] }}</td>
                <td>{{ $r['membership'] }}</td>
                <td>{{ $r['plan'] }}</td>
                <td>{{ number_format($r['price'], 2) }}</td>
                <td>{{ $r['starts_at'] }}</td>
                <td>{{ $r['expires_at'] }}</td>
                <td>{{ $r['date'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>