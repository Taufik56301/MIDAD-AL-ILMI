<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends Controller
{
    public function summary(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'app' => 'Percetakan Midad Al-Ilmi',
            'timestamp' => now()->toIso8601String(),
            'kpi' => [
                'today_revenue' => (float) Payment::query()->whereDate('date_paid', $today)->sum('amount_paid'),
                'month_revenue' => (float) Payment::query()
                    ->whereYear('date_paid', now()->year)
                    ->whereMonth('date_paid', now()->month)
                    ->sum('amount_paid'),
                'active_production_queue' => Order::query()
                    ->whereIn('payment_status', [Order::PAYMENT_DP, Order::PAYMENT_PAID])
                    ->whereIn('order_status', [Order::STATUS_ANTREAN, Order::STATUS_DIPRODUKSI])
                    ->count(),
                'ready_for_pickup' => Order::query()
                    ->where('order_status', Order::STATUS_SIAP_DIAMBIL)
                    ->count(),
            ],
            'low_stock_materials' => Material::query()
                ->lowStock()
                ->orderBy('current_stock')
                ->get(['id', 'name', 'type', 'current_stock', 'unit', 'minimal_stock_alert']),
        ]);
    }
}

