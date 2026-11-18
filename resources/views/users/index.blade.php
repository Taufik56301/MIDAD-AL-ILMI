@extends('layouts.app')

@section('title', 'Data Karyawan - Midad Al-Ilmi')
@section('header', 'Manajemen Karyawan & Hak Akses')
@section('subheader', 'Kelola akun Owner, Admin / Kasir, dan Operator Produksi')

@section('header-actions')
<a href="{{ route('users.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
    + Tambah Karyawan
</a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Nama</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Role / Jabatan</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $u)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold text-slate-900">{{ $u->name }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $u->email }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                                {{ $u->role === 'owner' ? 'bg-purple-100 text-purple-700' : ($u->role === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-sky-100 text-sky-700') }}">
                                {{ $u->roleLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-2">
                            <a href="{{ route('users.edit', $u) }}" class="text-indigo-600 hover:underline font-medium">Edit</a>
                            @if(auth()->id() !== $u->id)
                                <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('Hapus karyawan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:underline font-medium">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-200">{{ $users->links() }}</div>
</div>
@endsection

