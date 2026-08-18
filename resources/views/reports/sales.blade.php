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

    <div class="box">Total: <b>{{ number_format($summary['total_amount'], 2) }}</b></div>
    <div class="box">Invoices: <b>{{ $summary['total_count'] }}</b></div>
    <div class="box">Avg Ticket: <b>{{ number_format($summary['avg_ticket'], 2) }}</b></div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Seller</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr>
                <td>{{ $r['id'] }}</td>
                <td>{{ $r['customer'] }}</td>
                <td>{{ $r['phone'] }}</td>
                <td>{{ number_format($r['amount'], 2) }}</td>
                <td>{{ $r['payment'] }}</td>
                <td>{{ $r['seller'] }}</td>
                <td>{{ $r['date'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>