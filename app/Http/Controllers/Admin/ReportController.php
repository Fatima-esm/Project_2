<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\Salary;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function ensureAdmin()
    {
        if (!auth()->user() || auth()->user()->role !== 'admin') {
            abort(response()->json(['message' => 'Unauthorized'], 403));
        }
    }

    private function period(Request $request): array
    {
        if ($request->filled('month')) {
            $start = Carbon::parse($request->month . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
        } elseif ($request->filled('from') && $request->filled('to')) {
            $start = Carbon::parse($request->from)->startOfDay();
            $end   = Carbon::parse($request->to)->endOfDay();
        } else {
            $start = now()->startOfMonth();
            $end   = now()->endOfMonth();
        }

        return [$start, $end];
    }

    private function paymentLabel($m)
    {
        return match ($m) {
            'cash'     => 'Cash',
            'card'     => 'Card',
            'online'   => 'Online',
            'transfer' => 'Transfer',
            'bank'     => 'Bank',
            default    => $m ?? '—',
        };
    }

    private function roleLabel($role)
    {
        return match ($role) {
            'admin'     => 'Admin',
            'reception' => 'Reception',
            'coach'     => 'Coach',
            'trainee'   => 'Trainee',
            default     => $role ?? '—',
        };
    }

    private function downloadCsv(string $filename, array $headings, array $rows)
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function sales(Request $request)
    {
        $this->ensureAdmin();
        [$start, $end] = $this->period($request);

        $sales = Sale::with(['user:id,full_name,phone', 'seller:id,full_name'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();

        $summary = [
            'total_amount' => (float) $sales->sum('total_amount'),
            'total_count'  => $sales->count(),
            'avg_ticket'   => $sales->count()
                ? round($sales->sum('total_amount') / $sales->count(), 2)
                : 0,
        ];

        $rows = $sales->map(fn ($s) => [
            'id'       => $s->id,
            'customer' => $s->customer_name ?? $s->user?->full_name ?? 'Guest',
            'phone'    => $s->customer_phone ?? $s->user?->phone,
            'amount'   => (float) $s->total_amount,
            'payment'  => $this->paymentLabel($s->payment_method),
            'seller'   => $s->seller?->full_name ?? '—',
            'date'     => $s->created_at->format('Y-m-d H:i'),
        ]);

        $payload = [
            'status'  => 200,
            'title'   => 'Sales Report',
            'period'  => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
            'summary' => $summary,
            'rows'    => $rows,
        ];

        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('reports.sales', $payload)->setPaper('a4', 'portrait');
            return $pdf->download("sales_{$start->format('Y_m')}.pdf");
        }

        if ($request->get('export') === 'excel') {
            $csvRows = $rows->map(fn ($r) => [
                $r['id'], $r['customer'], $r['phone'], $r['amount'],
                $r['payment'], $r['seller'], $r['date'],
            ])->all();

            return $this->downloadCsv(
                "sales_{$start->format('Y_m')}.csv",
                ['#', 'Customer', 'Phone', 'Amount', 'Payment', 'Seller', 'Date'],
                $csvRows
            );
        }

        return response()->json($payload);
    }

    public function subscriptions(Request $request)
    {
        $this->ensureAdmin();
        [$start, $end] = $this->period($request);

        $subs = Subscription::with([
                'user:id,full_name,membership_number,phone',
                'plan:id,name,price,duration_days',
            ])
            ->where('status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();

        $summary = [
            'count'   => $subs->count(),
            'revenue' => (float) $subs->sum('price'),
        ];

        $rows = $subs->map(fn ($s) => [
            'id'         => $s->id,
            'trainee'    => $s->user?->full_name ?? '—',
            'membership' => $s->user?->membership_number ?? '—',
            'plan'       => $s->plan->name ?? '—',
            'price'      => (float) $s->price,
            'starts_at'  => optional($s->starts_at)->format('Y-m-d'),
            'expires_at' => optional($s->expires_at)->format('Y-m-d'),
            'date'       => $s->created_at->format('Y-m-d H:i'),
        ]);

        $payload = [
            'status'  => 200,
            'title'   => 'Subscription Renewals Report',
            'period'  => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
            'summary' => $summary,
            'rows'    => $rows,
        ];

        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('reports.subscriptions', $payload)->setPaper('a4', 'portrait');
            return $pdf->download("subscriptions_{$start->format('Y_m')}.pdf");
        }

        if ($request->get('export') === 'excel') {
            $csvRows = $rows->map(fn ($r) => [
                $r['id'], $r['trainee'], $r['membership'], $r['plan'],
                $r['price'], $r['starts_at'], $r['expires_at'], $r['date'],
            ])->all();

            return $this->downloadCsv(
                "subscriptions_{$start->format('Y_m')}.csv",
                ['#', 'Trainee', 'Membership', 'Plan', 'Price', 'Starts At', 'Expires At', 'Created At'],
                $csvRows
            );
        }

        return response()->json($payload);
    }

    public function salaries(Request $request)
    {
        $this->ensureAdmin();
        [$start, $end] = $this->period($request);
        $monthFrom = $start->format('Y-m');
        $monthTo   = $end->format('Y-m');

        $items = Salary::with('user:id,full_name,role,membership_number')
            ->whereBetween('month', [$monthFrom, $monthTo])
            ->orderBy('month', 'desc')
            ->get();

        $summary = [
            'total_net'     => (float) $items->sum('net_salary'),
            'paid_count'    => $items->where('status', 'paid')->count(),
            'pending_count' => $items->where('status', 'pending')->count(),
        ];

        $rows = $items->map(fn ($s) => [
            'id'         => $s->id,
            'employee'   => $s->user?->full_name ?? '—',
            'role'       => $this->roleLabel($s->user?->role),
            'membership' => $s->user?->membership_number ?? '—',
            'month'      => $s->month,
            'base'       => (float) $s->base_salary,
            'bonus'      => (float) $s->bonus,
            'deduction'  => (float) $s->deduction,
            'net'        => (float) $s->net_salary,
            'status'     => $s->status === 'paid' ? 'Paid' : 'Pending',
        ]);

        $payload = [
            'status'  => 200,
            'title'   => 'Salaries Report',
            'period'  => ['from' => $monthFrom, 'to' => $monthTo],
            'summary' => $summary,
            'rows'    => $rows,
        ];

        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('reports.salaries', $payload)->setPaper('a4', 'portrait');
            return $pdf->download("salaries_{$monthFrom}.pdf");
        }

        if ($request->get('export') === 'excel') {
            $csvRows = $rows->map(fn ($r) => [
                $r['id'], $r['employee'], $r['role'], $r['membership'], $r['month'],
                $r['base'], $r['bonus'], $r['deduction'], $r['net'], $r['status'],
            ])->all();

            return $this->downloadCsv(
                "salaries_{$monthFrom}.csv",
                ['#', 'Employee', 'Role', 'Membership', 'Month', 'Base', 'Bonus', 'Deduction', 'Net', 'Status'],
                $csvRows
            );
        }

        return response()->json($payload);
    }

    public function staffActivity(Request $request)
    {
        $this->ensureAdmin();
        [$start, $end] = $this->period($request);

        $query = ActivityLog::with('user:id,full_name,role')
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('user', fn ($q) => $q->whereIn('role', ['reception', 'admin']))
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->limit(200)->get();

        $summary = ['total' => $logs->count()];

        $rows = $logs->map(function ($log) {
            $details = $log->details;
            if (is_array($details)) {
                $details = $details['message'] ?? json_encode($details, JSON_UNESCAPED_UNICODE);
            }
            if (is_object($details)) {
                $details = json_encode($details, JSON_UNESCAPED_UNICODE);
            }

            return [
                'id'       => $log->id,
                'employee' => $log->user?->full_name ?? '—',
                'role'     => $this->roleLabel($log->user?->role),
                'date'     => $log->created_at->format('Y-m-d'),
                'time'     => $log->created_at->format('H:i'),
                'action'   => $log->action_label ?? $log->action ?? '—',
                'details'  => is_string($details) ? $details : '—',
            ];
        });

        $payload = [
            'status'  => 200,
            'title'   => 'Staff Activity Report',
            'period'  => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
            'summary' => $summary,
            'rows'    => $rows,
        ];

        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('reports.staff_activity', $payload)->setPaper('a4', 'portrait');
            return $pdf->download("staff_activity_{$start->format('Y_m')}.pdf");
        }

        if ($request->get('export') === 'excel') {
            $csvRows = $rows->map(fn ($r) => [
                $r['id'], $r['employee'], $r['role'], $r['date'],
                $r['time'], $r['action'], $r['details'],
            ])->all();

            return $this->downloadCsv(
                "staff_activity_{$start->format('Y_m')}.csv",
                ['#', 'Employee', 'Role', 'Date', 'Time', 'Action', 'Details'],
                $csvRows
            );
        }

        return response()->json($payload);
    }
}