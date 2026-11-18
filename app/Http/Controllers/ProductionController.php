<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'active');

        $orders = Order::query()
            ->with(['customer', 'details.material', 'payments'])
            ->whereIn('payment_status', [Order::PAYMENT_DP, Order::PAYMENT_PAID])
            ->when($statusFilter === 'active', function ($q): void {
                $q->whereIn('order_status', [
                    Order::STATUS_ANTREAN,
                    Order::STATUS_DIPRODUKSI,
                    Order::STATUS_SIAP_DIAMBIL,
                ]);
            }, function ($q) use ($statusFilter): void {
                if (in_array($statusFilter, Order::ORDER_STATUSES, true)) {
                    $q->where('order_status', $statusFilter);
                }
            })
            ->orderByRaw("CASE order_status WHEN 'diproduksi' THEN 1 WHEN 'antrean' THEN 2 WHEN 'siap_diambil' THEN 3 ELSE 4 END")
            ->orderBy('created_at')
            ->paginate(12)
            ->withQueryString();

        $counts = [
            'antrean' => Order::query()->whereIn('payment_status', [Order::PAYMENT_DP, Order::PAYMENT_PAID])->where('order_status', Order::STATUS_ANTREAN)->count(),
            'diproduksi' => Order::query()->whereIn('payment_status', [Order::PAYMENT_DP, Order::PAYMENT_PAID])->where('order_status', Order::STATUS_DIPRODUKSI)->count(),
            'siap_diambil' => Order::query()->whereIn('payment_status', [Order::PAYMENT_DP, Order::PAYMENT_PAID])->where('order_status', Order::STATUS_SIAP_DIAMBIL)->count(),
        ];

        return view('production.index', compact('orders', 'statusFilter', 'counts'));
    }
}

