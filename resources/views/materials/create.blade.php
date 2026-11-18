@extends('layouts.app')

@section('title', 'Tambah Bahan Baku - Midad Al-Ilmi')
@section('header', 'Tambah Master Bahan Baku')

@section('content')
<div class="max-w-xl bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <form action="{{ route('materials.store') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Bahan</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Mis. Flexi Korea 440gr" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Jenis / Kategori</label>
            <input type="text" name="type" value="{{ old('type') }}" required placeholder="Mis. Banner / Kertas / Tinta" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Stok Awal</label>
                <input type="number" step="0.01" min="0" name="current_stock" value="{{ old('current_stock', 0) }}" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Satuan</label>
                <input type="text" name="unit" value="{{ old('unit', 'lembar') }}" required placeholder="lembar / meter / botol" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Batas Alert Min.</label>
                <input type="number" step="0.01" min="0" name="minimal_stock_alert" value="{{ old('minimal_stock_alert', 20) }}" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
            </div>
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Simpan Bahan</button>
            <a href="{{ route('materials.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Batal</a>
        </div>
    </form>
</div>
@endsection

