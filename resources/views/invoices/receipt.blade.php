<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details th, .details td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .footer { margin-top: 50px; text-align: center; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment Receipt - Fitness Club</h1>
    </div>
    <table class="details">
        <tr><th>Transaction ID</th><td>{{ $transaction->transaction_number }}</td></tr>
        <tr><th>Member Name</th><td>{{ $user->full_name }}</td></tr>
        <tr><th>Plan Name</th><td>{{ $transaction->subscription->plan->name }}</td></tr>
        <tr><th>Amount Paid</th><td>{{ $transaction->amount }} SAR</td></tr>
        <tr><th>Date</th><td>{{ $transaction->created_at->format('Y-m-d') }}</td></tr>
    </table>
    <div class="footer">
        <p>Thank you for trusting our club!</p>
    </div>
</body>
</html>
