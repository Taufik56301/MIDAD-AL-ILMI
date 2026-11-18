<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Material;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now()->toDateString();

        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $lowStockMaterials = Material::query()
            ->lowStock()
            ->orderBy('current_stock')
            ->get();

        $activeQueueCount = Order::query()
            ->whereIn('payment_status', [Order::PAYMENT_DP, Order::PAYMENT_PAID])
            ->whereIn('order_status', [Order::STATUS_ANTREAN, Order::STATUS_DIPRODUKSI])
            ->count();

        $readyPickupCount = Order::query()
            ->where('order_status', Order::STATUS_SIAP_DIAMBIL)
            ->count();

        $recentOrders = Order::query()
            ->with(['customer', 'details.material'])
            ->when($user->isOperator(), fn ($q) => $q->whereIn('payment_status', [Order::PAYMENT_DP, Order::PAYMENT_PAID])
                ->whereIn('order_status', [Order::STATUS_ANTREAN, Order::STATUS_DIPRODUKSI, Order::STATUS_SIAP_DIAMBIL]))
            ->latest()
            ->limit(8)
            ->get();

        $todayRevenue = 0.0;
        $monthRevenue = 0.0;
        $unpaidOrdersCount = 0;
        $chartLabels = [];
        $chartValues = [];

        if ($user->hasRole('owner', 'admin')) {
            $todayRevenue = (float) Payment::query()
                ->whereDate('date_paid', $today)
                ->sum('amount_paid');

            $monthRevenue = (float) Payment::query()
                ->whereYear('date_paid', now()->year)
                ->whereMonth('date_paid', now()->month)
                ->sum('amount_paid');

            $unpaidOrdersCount = Order::query()
                ->whereIn('payment_status', [Order::PAYMENT_UNPAID, Order::PAYMENT_DP])
                ->where('order_status', '!=', Order::STATUS_CLOSED)
                ->count();

            $startDate = now()->subDays(13)->startOfDay();
            $paymentsByDay = Payment::query()
                ->where('date_paid', '>=', $startDate)
                ->get(['date_paid', 'amount_paid'])
                ->groupBy(fn (Payment $p) => Carbon::parse($p->date_paid)->toDateString())
                ->map(fn ($group) => (float) $group->sum('amount_paid'));

            for ($i = 13; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $dateKey = $day->toDateString();
                $chartLabels[] = $day->format('d M');
                $chartValues[] = (float) ($paymentsByDay->get($dateKey) ?? 0.0);
            }
        }

        return view('dashboard', compact(
            'todayAttendance',
            'lowStockMaterials',
            'activeQueueCount',
            'readyPickupCount',
            'recentOrders',
            'todayRevenue',
            'monthRevenue',
            'unpaidOrdersCount',
            'chartLabels',
            'chartValues'
        ));
    }
}

