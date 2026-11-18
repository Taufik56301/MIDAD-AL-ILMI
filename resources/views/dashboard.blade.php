@extends('layouts.app')

@section('title', 'Dashboard - Midad Al-Ilmi')
@section('header', 'Dashboard Operasional')
@section('subheader', 'Ringkasan aktivitas harian Percetakan Midad Al-Ilmi')

@section('content')
{{-- Widget Absensi Cepat (Tahap 1 & 6) --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Absensi Hari Ini ({{ now()->translatedFormat('d M Y') }})</div>
        @if(!$todayAttendance)
            <div class="text-base font-semibold text-slate-800 mt-1">Anda belum melakukan Absen Masuk hari ini.</div>
        @elseif(!$todayAttendance->clock_out)
            <div class="text-base font-semibold text-emerald-700 mt-1">
                Sudah Absen Masuk pukul {{ $todayAttendance->clock_in->format('H:i:s') }} WIB
            </div>
        @else
            <div class="text-base font-semibold text-slate-800 mt-1">
                Selesai bekerja: {{ $todayAttendance->clock_in->format('H:i') }} - {{ $todayAttendance->clock_out->format('H:i') }} WIB
                ({{ number_format($todayAttendance->total_hours, 2) }} jam)
            </div>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @if(!$todayAttendance)
            <form action="{{ route('attendances.clock-in') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                    Absen Masuk
                </button>
            </form>
        @elseif(!$todayAttendance->clock_out)
            <form action="{{ route('attendances.clock-out') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700">
                    Absen Pulang
                </button>
            </form>
        @endif
        <a href="{{ route('attendances.index') }}" class="px-3.5 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Riwayat Absensi
        </a>
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="text-xs font-medium text-slate-500">Antrean & Sedang Diproduksi</div>
        <div class="text-3xl font-bold text-indigo-600 mt-2">{{ $activeQueueCount }}</div>
        <div class="text-xs text-slate-400 mt-1">Order berstatus DP / Lunas</div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="text-xs font-medium text-slate-500">Siap Diambil Pelanggan</div>
        <div class="text-3xl font-bold text-emerald-600 mt-2">{{ $readyPickupCount }}</div>
        <div class="text-xs text-slate-400 mt-1">Menunggu pengambilan / pelunasan</div>
    </div>

    @if(auth()->user()->hasRole('owner', 'admin'))
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="text-xs font-medium text-slate-500">Kas Masuk Hari Ini</div>
            <div class="text-2xl font-bold text-slate-900 mt-2">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-400 mt-1">Bulan ini: Rp {{ number_format($monthRevenue, 0, ',', '.') }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="text-xs font-medium text-slate-500">Tagihan Belum Lunas</div>
            <div class="text-3xl font-bold text-amber-600 mt-2">{{ $unpaidOrdersCount }}</div>
            <div class="text-xs text-slate-400 mt-1">Status Belum Bayar / DP</div>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="text-xs font-medium text-slate-500">Bahan Baku Kritis</div>
            <div class="text-3xl font-bold text-rose-600 mt-2">{{ $lowStockMaterials->count() }}</div>
            <div class="text-xs text-slate-400 mt-1">Stok &le; batas minimum</div>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Grafik Omset Harian (Khusus Admin & Owner) --}}
    @if(auth()->user()->hasRole('owner', 'admin'))
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Grafik Kas Masuk Harian (14 Hari Terakhir)</h2>
                    <p class="text-xs text-slate-500">Akumulasi pembayaran DP & Pelunasan per hari</p>
                </div>
                @if(auth()->user()->isOwner())
                    <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Buka Laporan Lengkap &rarr;</a>
                @endif
            </div>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    @endif

    {{-- Warning Box Stok Menipis --}}
    <div class="{{ auth()->user()->hasRole('owner', 'admin') ? 'lg:col-span-1' : 'lg:col-span-3' }} bg-white rounded-xl border {{ $lowStockMaterials->isNotEmpty() ? 'border-rose-300' : 'border-slate-200' }} shadow-sm p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-bold {{ $lowStockMaterials->isNotEmpty() ? 'text-rose-700' : 'text-slate-900' }}">
                Warning Box: Stok Bahan Menipis
            </h2>
            <a href="{{ route('materials.index', ['filter' => 'low']) }}" class="text-xs font-medium text-indigo-600 hover:underline">Lihat Semua</a>
        </div>

        @if($lowStockMaterials->isEmpty())
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700">
                Seluruh stok bahan baku gudang dalam kondisi aman di atas batas minimum.
            </div>
        @else
            <div class="space-y-2.5">
                @foreach($lowStockMaterials as $mat)
                    <div class="rounded-lg bg-rose-50 border border-rose-200 px-3.5 py-2.5 flex items-center justify-between gap-2">
                        <div>
                            <div class="text-sm font-semibold text-rose-900">{{ $mat->name }}</div>
                            <div class="text-xs text-rose-700">Batas min: {{ number_format($mat->minimal_stock_alert, 0, ',', '.') }} {{ $mat->unit }}</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-rose-600 text-white text-xs font-bold whitespace-nowrap">
                            Sisa {{ $mat->formattedStock() }} {{ $mat->unit }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Pesanan Terbaru / Antrean --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-base font-bold text-slate-900">
            {{ auth()->user()->isOperator() ? 'Antrean Produksi Aktif' : 'Pesanan Terbaru' }}
        </h2>
        <a href="{{ auth()->user()->isOperator() ? route('production.index') : route('orders.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">
            Lihat Semua &rarr;
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">No. Order</th>
                    <th class="px-5 py-3">Pelanggan</th>
                    <th class="px-5 py-3">Item</th>
                    <th class="px-5 py-3">Pembayaran</th>
                    <th class="px-5 py-3">Status Produksi</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentOrders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold text-slate-900">{{ $order->order_number }}</td>
                        <td class="px-5 py-3">{{ $order->customer->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $order->details->pluck('product_name')->implode(', ') }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($order->payment_status === 'dp' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                {{ $order->paymentStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $order->order_status === 'diproduksi' ? 'bg-indigo-100 text-indigo-700' : ($order->order_status === 'siap_diambil' ? 'bg-emerald-100 text-emerald-700' : ($order->order_status === 'closed' ? 'bg-slate-200 text-slate-700' : 'bg-sky-100 text-sky-700')) }}">
                                {{ $order->orderStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada data pesanan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@if(auth()->user()->hasRole('owner', 'admin'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('revenueChart');
    if (!canvas || typeof window.Chart === 'undefined') return;
    new window.Chart(canvas, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Kas Masuk (Rp)',
                data: @json($chartValues),
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (val) => 'Rp ' + Number(val).toLocaleString('id-ID')
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endif

