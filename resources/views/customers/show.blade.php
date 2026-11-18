@extends('layouts.app')

@section('title', 'Riwayat Pelanggan - Midad Al-Ilmi')
@section('header', $customer->name)
@section('subheader', 'WhatsApp: '.$customer->phone_whatsapp)

@section('header-actions')
<a href="{{ $customer->whatsappUrl() }}" target="_blank" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
    Chat WhatsApp
</a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900">Riwayat Pesanan</h2>
        <a href="{{ route('orders.create', ['customer_id' => $customer->id]) }}" class="text-sm font-semibold text-indigo-600 hover:underline">+ Buat Order Baru</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">No. Order</th>
                    <th class="px-5 py-3">Tanggal</th>
                    <th class="px-5 py-3">Total</th>
                    <th class="px-5 py-3">Pembayaran</th>
                    <th class="px-5 py-3">Produksi</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold">{{ $order->order_number }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 font-semibold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">{{ $order->paymentStatusLabel() }}</td>
                        <td class="px-5 py-3">{{ $order->orderStatusLabel() }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:underline font-medium">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Pelanggan ini belum memiliki pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-200">{{ $orders->links() }}</div>
</div>
@endsection

