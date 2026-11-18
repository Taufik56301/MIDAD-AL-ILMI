@extends('layouts.app')

@section('title', 'Laporan & Ekspor - Midad Al-Ilmi')
@section('header', 'Laporan Manajerial & Ekspor Periodik')
@section('subheader', 'Tahap 7: Khusus Owner / Pemilik — Ekspor ke Excel (.xlsx) dan PDF')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
    <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
            Terapkan Periode
        </button>
    </form>
</div>

{{-- Tab Menu Laporan --}}
<div class="flex flex-wrap gap-2">
    @php
        $tabs = [
            'sales' => '1. Penjualan & Omset',
            'cashflow' => '2. Kas Masuk (DP & Lunas)',
            'inventory' => '3. Sisa Stok Bahan',
            'attendance' => '4. Kehadiran Karyawan',
        ];
    @endphp
    @foreach($tabs as $key => $label)
        <a href="{{ route('reports.index', ['tab' => $key, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold {{ $tab === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- Panel Isi Laporan --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-bold text-slate-900">{{ $tabs[$tab] ?? 'Laporan' }}</h2>
            <p class="text-xs text-slate-500">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.excel', ['type' => $tab, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
               class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                Export Excel (.xlsx)
            </a>
            <a href="{{ route('reports.pdf', ['type' => $tab, 'start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank"
               class="px-3.5 py-2 rounded-lg bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700">
                Export PDF
            </a>
        </div>
    </div>

    @if($tab === 'sales')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 bg-slate-50 border-b border-slate-200">
            <div>
                <div class="text-xs text-slate-500">Jumlah Order Masuk</div>
                <div class="text-2xl font-bold text-slate-900">{{ $sales['total_count'] }} Pesanan</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Total Omset Penjualan</div>
                <div class="text-2xl font-bold text-indigo-700">Rp {{ number_format($sales['total_omset'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">No. Order</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Pelanggan</th>
                        <th class="px-5 py-3">Status Bayar</th>
                        <th class="px-5 py-3">Status Produksi</th>
                        <th class="px-5 py-3 text-right">Total Tagihan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sales['orders'] as $o)
                        <tr>
                            <td class="px-5 py-3 font-semibold">{{ $o->order_number }}</td>
                            <td class="px-5 py-3">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">{{ $o->customer->name ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $o->paymentStatusLabel() }}</td>
                            <td class="px-5 py-3">{{ $o->orderStatusLabel() }}</td>
                            <td class="px-5 py-3 text-right font-bold">Rp {{ number_format($o->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">Tidak ada transaksi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif($tab === 'cashflow')
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-5 bg-slate-50 border-b border-slate-200">
            <div>
                <div class="text-xs text-slate-500">Total Kas Masuk</div>
                <div class="text-2xl font-bold text-emerald-700">Rp {{ number_format($cashflow['total_cash_in'], 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Dari Uang Muka (DP)</div>
                <div class="text-xl font-bold text-amber-700">Rp {{ number_format($cashflow['total_dp'], 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Dari Pelunasan</div>
                <div class="text-xl font-bold text-indigo-700">Rp {{ number_format($cashflow['total_lunas'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Tanggal Bayar</th>
                        <th class="px-5 py-3">No. Order</th>
                        <th class="px-5 py-3">Pelanggan</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3">Metode</th>
                        <th class="px-5 py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($cashflow['payments'] as $p)
                        <tr>
                            <td class="px-5 py-3">{{ \Carbon\Carbon::parse($p->date_paid)->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 font-semibold">{{ $p->order->order_number ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $p->order->customer->name ?? '-' }}</td>
                            <td class="px-5 py-3 uppercase text-xs font-semibold">{{ $p->payment_type }}</td>
                            <td class="px-5 py-3 uppercase text-xs">{{ $p->payment_method }}</td>
                            <td class="px-5 py-3 text-right font-bold">Rp {{ number_format($p->amount_paid, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">Tidak ada kas masuk pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif($tab === 'inventory')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 bg-slate-50 border-b border-slate-200">
            <div>
                <div class="text-xs text-slate-500">Total Jenis Bahan</div>
                <div class="text-2xl font-bold text-slate-900">{{ $inventory['materials']->count() }} Bahan</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Bahan di Bawah Batas Minimum</div>
                <div class="text-2xl font-bold text-rose-600">{{ $inventory['low_stock_count'] }} Bahan</div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Nama Bahan</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3 text-right">Sisa Stok</th>
                        <th class="px-5 py-3 text-right">Batas Min.</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($inventory['materials'] as $m)
                        <tr>
                            <td class="px-5 py-3 font-semibold">{{ $m->name }}</td>
                            <td class="px-5 py-3">{{ $m->type }}</td>
                            <td class="px-5 py-3 text-right font-bold">{{ $m->formattedStock() }} {{ $m->unit }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($m->minimal_stock_alert, 0, ',', '.') }} {{ $m->unit }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $m->isLowStock() ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $m->isLowStock() ? 'Stok Menipis' : 'Aman' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 bg-slate-50 border-b border-slate-200">
            <div>
                <div class="text-xs text-slate-500">Total Catatan Kehadiran</div>
                <div class="text-2xl font-bold text-slate-900">{{ $attendance['attendances']->count() }} Hari Kerja</div>
            </div>
            <div>
                <div class="text-xs text-slate-500">Akumulasi Jam Kerja</div>
                <div class="text-2xl font-bold text-indigo-700">{{ $attendance['total_hours'] }} Jam</div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Karyawan</th>
                        <th class="px-5 py-3">Jabatan</th>
                        <th class="px-5 py-3">Masuk</th>
                        <th class="px-5 py-3">Pulang</th>
                        <th class="px-5 py-3 text-right">Total Jam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attendance['attendances'] as $att)
                        <tr>
                            <td class="px-5 py-3">{{ $att->date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 font-semibold">{{ $att->user->name ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $att->user?->roleLabel() ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $att->clock_in?->format('H:i:s') ?? '-' }}</td>
                            <td class="px-5 py-3">{{ $att->clock_out?->format('H:i:s') ?? 'Belum Pulang' }}</td>
                            <td class="px-5 py-3 text-right font-bold">{{ number_format($att->total_hours, 2) }} jam</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">Belum ada data kehadiran pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

