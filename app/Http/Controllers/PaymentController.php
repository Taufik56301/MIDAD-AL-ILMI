<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $unpaidOrders = Order::query()
            ->with(['customer', 'payments'])
            ->whereIn('payment_status', [Order::PAYMENT_UNPAID, Order::PAYMENT_DP])
            ->latest()
            ->get();

        $payments = Payment::query()
            ->with(['order.customer', 'user'])
            ->latest('date_paid')
            ->paginate(15);

        return view('payments.index', compact('unpaidOrders', 'payments'));
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in([Payment::METHOD_TUNAI, Payment::METHOD_TRANSFER])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $remaining = $order->remaining_balance;
        if ($remaining <= 0) {
            return back()->with('error', 'Pesanan ini sudah berstatus Lunas.');
        }

        $amountPaid = round((float) $validated['amount_paid'], 2);
        if ($amountPaid > $remaining + 0.01) {
            return back()
                ->withInput()
                ->withErrors([
                    'amount_paid' => 'Nominal pembayaran melebihi sisa tagihan sebesar Rp '.number_format($remaining, 0, ',', '.').'.',
                ]);
        }

        DB::transaction(function () use ($request, $order, $validated, $amountPaid, $remaining): void {
            $paymentType = ($amountPaid + 0.001 >= $remaining)
                ? Payment::TYPE_LUNAS
                : Payment::TYPE_DP;

            Payment::create([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'amount_paid' => $amountPaid,
                'payment_type' => $paymentType,
                'payment_method' => $validated['payment_method'],
                'date_paid' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->syncPaymentStatus();
        });

        $order->refresh();

        return back()->with(
            'success',
            "Pembayaran sebesar Rp ".number_format($amountPaid, 0, ',', '.')." berhasil dicatat. Status saat ini: {$order->paymentStatusLabel()}."
        );
    }

    public function invoicePdf(Request $request, Order $order): Response
    {
        $order->load(['customer', 'user', 'details.material', 'payments']);

        $pdf = Pdf::loadView('pdfs.invoice', compact('order'))
            ->setPaper('a4', 'portrait');

        $filename = "Nota-{$order->order_number}.pdf";

        return $request->boolean('download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}

