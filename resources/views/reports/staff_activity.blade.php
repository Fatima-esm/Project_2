<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        .meta { color: #555; margin-bottom: 14px; }
        .box { border: 1px solid #ddd; padding: 8px 12px; display: inline-block; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Period: {{ $period['from'] }} — {{ $period['to'] }}</div>

    <div class="box">Total Activities: <b>{{ $summary['total'] }}</b></div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee</th>
                <th>Role</th>
                <th>Date</th>
                <th>Time</th>
                <th>Action</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr>
                <td>{{ $r['id'] }}</td>
                <td>{{ $r['employee'] }}</td>
                <td>{{ $r['role'] }}</td>
                <td>{{ $r['date'] }}</td>
                <td>{{ $r['time'] }}</td>
                <td>{{ $r['action'] }}</td>
                <td>{{ $r['details'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>