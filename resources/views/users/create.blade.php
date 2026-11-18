@extends('layouts.app')

@section('title', 'Tambah Karyawan - Midad Al-Ilmi')
@section('header', 'Tambah Karyawan Baru')

@section('content')
<div class="max-w-xl bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Role / Jabatan</label>
            <select name="role" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
                <option value="admin" @selected(old('role') === 'admin')>Admin / Kasir</option>
                <option value="operator" @selected(old('role') === 'operator')>Operator Produksi</option>
                <option value="owner" @selected(old('role') === 'owner')>Owner / Pemilik</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
            <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Kata Sandi</label>
            <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">Batal</a>
        </div>
    </form>
</div>
@endsection

