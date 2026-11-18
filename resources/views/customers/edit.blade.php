@extends('layouts.app')

@section('title', 'Edit Pelanggan - Midad Al-Ilmi')
@section('header', 'Edit Data Pelanggan')

@section('content')
<div class="max-w-xl bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <form action="{{ route('customers.update', $customer) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Pelanggan / Instansi</label>
            <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nomor WhatsApp</label>
            <input type="text" name="phone_whatsapp" value="{{ old('phone_whatsapp', $customer->phone_whatsapp) }}" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Alamat / Keterangan (Opsional)</label>
            <input type="text" name="address" value="{{ old('address', $customer->address) }}" class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Perbarui</button>
            <a href="{{ route('customers.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700">Batal</a>
        </div>
    </form>
</div>
@endsection

