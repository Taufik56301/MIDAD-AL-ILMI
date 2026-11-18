@extends('layouts.app')

@section('title', 'Pelanggan (CRM) - Midad Al-Ilmi')
@section('header', 'Direktori Pelanggan (CRM)')
@section('subheader', 'Basis data kontak WhatsApp dan riwayat pemesanan pelanggan')

@section('header-actions')
<a href="{{ route('customers.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
    + Tambah Pelanggan
</a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
    <form method="GET" action="{{ route('customers.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau No. WhatsApp..."
               class="flex-1 min-w-[220px] rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium">Cari</button>
        @if($search)
            <a href="{{ route('customers.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-600">Reset</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Nama Pelanggan</th>
                    <th class="px-5 py-3">No. WhatsApp</th>
                    <th class="px-5 py-3">Alamat / Instansi</th>
                    <th class="px-5 py-3 text-center">Total Order</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($customers as $customer)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold text-slate-900">
                            <a href="{{ route('customers.show', $customer) }}" class="hover:text-indigo-600">{{ $customer->name }}</a>
                        </td>
                        <td class="px-5 py-3">
                            <a href="{{ $customer->whatsappUrl() }}" target="_blank" class="text-emerald-700 hover:underline font-medium">
                                {{ $customer->phone_whatsapp }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $customer->address ?: '-' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">{{ $customer->orders_count }}</span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-2">
                            <a href="{{ route('customers.show', $customer) }}" class="text-slate-700 hover:underline font-medium">Riwayat</a>
                            <a href="{{ route('customers.edit', $customer) }}" class="text-indigo-600 hover:underline font-medium">Edit</a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pelanggan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:underline font-medium">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">Belum ada data pelanggan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-200">{{ $customers->links() }}</div>
</div>
@endsection

