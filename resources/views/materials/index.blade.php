@extends('layouts.app')

@section('title', 'Stok Bahan Baku - Midad Al-Ilmi')
@section('header', 'Inventori & Master Bahan Baku')
@section('subheader', 'Stok terpotong otomatis oleh OrderObserver saat pesanan masuk tahap Sedang Diproduksi')

@section('header-actions')
@if(auth()->user()->hasRole('admin', 'owner'))
    <a href="{{ route('materials.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
        + Tambah Bahan Baku
    </a>
@endif
@endsection

@section('content')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('materials.index') }}" class="px-3.5 py-1.5 rounded-lg text-sm font-medium {{ $filter !== 'low' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-300 text-slate-700' }}">
        Semua Bahan
    </a>
    <a href="{{ route('materials.index', ['filter' => 'low']) }}" class="px-3.5 py-1.5 rounded-lg text-sm font-medium {{ $filter === 'low' ? 'bg-rose-600 text-white' : 'bg-white border border-rose-300 text-rose-700' }}">
        Stok Menipis ({{ $lowStockCount }})
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Nama Bahan</th>
                    <th class="px-5 py-3">Jenis</th>
                    <th class="px-5 py-3 text-right">Stok Saat Ini</th>
                    <th class="px-5 py-3 text-right">Batas Minimum</th>
                    <th class="px-5 py-3">Status</th>
                    @if(auth()->user()->hasRole('admin', 'owner'))
                        <th class="px-5 py-3">Restock Cepat</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($materials as $mat)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold text-slate-900">{{ $mat->name }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $mat->type }}</td>
                        <td class="px-5 py-3 text-right font-bold {{ $mat->isLowStock() ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ $mat->formattedStock() }} {{ $mat->unit }}
                        </td>
                        <td class="px-5 py-3 text-right text-slate-500">{{ number_format($mat->minimal_stock_alert, 0, ',', '.') }} {{ $mat->unit }}</td>
                        <td class="px-5 py-3">
                            @if($mat->current_stock <= 0)
                                <span class="px-2.5 py-0.5 rounded-full bg-rose-600 text-white text-xs font-semibold">Habis</span>
                            @elseif($mat->isLowStock())
                                <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-700 text-xs font-semibold">Menipis</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">Aman</span>
                            @endif
                        </td>
                        @if(auth()->user()->hasRole('admin', 'owner'))
                            <td class="px-5 py-3">
                                <form action="{{ route('materials.restock', $mat) }}" method="POST" class="flex items-center gap-1.5">
                                    @csrf
                                    <input type="number" step="0.01" min="0.01" name="add_stock" placeholder="+ Qty" required
                                           class="w-24 rounded-md border border-slate-300 px-2 py-1 text-xs">
                                    <button type="submit" class="px-2.5 py-1 rounded-md bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">Tambah</button>
                                </form>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2">
                                <a href="{{ route('materials.edit', $mat) }}" class="text-indigo-600 hover:underline font-medium">Edit</a>
                                <form action="{{ route('materials.destroy', $mat) }}" method="POST" class="inline" onsubmit="return confirm('Hapus bahan baku ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:underline font-medium">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">Belum ada data bahan baku.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-200">{{ $materials->links() }}</div>
</div>
@endsection

