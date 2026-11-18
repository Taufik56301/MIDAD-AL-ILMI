<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Material;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Normalize date range defaults for reports.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function resolvePeriod(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth()->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array{orders: Collection, total_omset: float, total_count: int}
     */
    public function salesData(Carbon $start, Carbon $end): array
    {
        $orders = Order::query()
            ->with(['customer', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();

        return [
            'orders' => $orders,
            'total_omset' => (float) $orders->sum('total_amount'),
            'total_count' => $orders->count(),
        ];
    }

    /**
     * @return array{payments: Collection, total_cash_in: float, total_dp: float, total_lunas: float, total_tunai: float, total_transfer: float}
     */
    public function cashflowData(Carbon $start, Carbon $end): array
    {
        $payments = Payment::query()
            ->with(['order.customer', 'user'])
            ->whereBetween('date_paid', [$start, $end])
            ->latest('date_paid')
            ->get();

        return [
            'payments' => $payments,
            'total_cash_in' => (float) $payments->sum('amount_paid'),
            'total_dp' => (float) $payments->where('payment_type', Payment::TYPE_DP)->sum('amount_paid'),
            'total_lunas' => (float) $payments->where('payment_type', Payment::TYPE_LUNAS)->sum('amount_paid'),
            'total_tunai' => (float) $payments->where('payment_method', Payment::METHOD_TUNAI)->sum('amount_paid'),
            'total_transfer' => (float) $payments->where('payment_method', Payment::METHOD_TRANSFER)->sum('amount_paid'),
        ];
    }

    /**
     * @return array{materials: Collection, low_stock_count: int}
     */
    public function inventoryData(): array
    {
        $materials = Material::query()->orderBy('name')->get();

        return [
            'materials' => $materials,
            'low_stock_count' => $materials->filter(fn (Material $m) => $m->isLowStock())->count(),
        ];
    }

    /**
     * @return array{attendances: Collection, total_hours: float, summary_by_user: Collection}
     */
    public function attendanceData(Carbon $start, Carbon $end): array
    {
        $attendances = Attendance::query()
            ->with('user')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('date')
            ->orderBy('user_id')
            ->get();

        $summaryByUser = $attendances->groupBy('user_id')->map(function (Collection $rows): array {
            $user = $rows->first()?->user;

            return [
                'name' => $user?->name ?? 'Karyawan',
                'role' => $user?->roleLabel() ?? '-',
                'days_present' => $rows->count(),
                'total_hours' => round((float) $rows->sum('total_hours'), 2),
            ];
        })->values();

        return [
            'attendances' => $attendances,
            'total_hours' => round((float) $attendances->sum('total_hours'), 2),
            'summary_by_user' => $summaryByUser,
        ];
    }
}

