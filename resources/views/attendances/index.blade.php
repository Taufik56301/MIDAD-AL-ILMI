@extends('layouts.app')

@section('title', 'Absensi Harian - Midad Al-Ilmi')
@section('header', 'Absensi Karyawan (Real-Time)')
@section('subheader', 'Tahap 1 & Tahap 6: Pencatatan jam masuk, jam pulang, dan akumulasi durasi kerja')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status Absensi Anda Hari Ini ({{ now()->translatedFormat('d F Y') }})</div>
        @if(!$todayAttendance)
            <div class="text-lg font-bold text-slate-900 mt-1">Belum Absen Masuk</div>
            <p class="text-sm text-slate-500">Tekan tombol Absen Masuk saat tiba di lokasi percetakan.</p>
        @elseif(!$todayAttendance->clock_out)
            <div class="text-lg font-bold text-emerald-700 mt-1">Aktif Bekerja (Masuk: {{ $todayAttendance->clock_in->format('H:i:s') }} WIB)</div>
            <p class="text-sm text-slate-500">Jangan lupa tekan tombol Absen Pulang saat jam operasional berakhir.</p>
        @else
            <div class="text-lg font-bold text-indigo-700 mt-1">Absensi Hari Ini Selesai</div>
            <p class="text-sm text-slate-600">
                Masuk: <strong>{{ $todayAttendance->clock_in->format('H:i:s') }}</strong> |
                Pulang: <strong>{{ $todayAttendance->clock_out->format('H:i:s') }}</strong> |
                Total: <strong>{{ number_format($todayAttendance->total_hours, 2) }} jam</strong>
            </p>
        @endif
    </div>

    <div class="flex items-center gap-3">
        @if(!$todayAttendance)
            <form action="{{ route('attendances.clock-in') }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 shadow-sm">
                    Absen Masuk Sekarang
                </button>
            </form>
        @elseif(!$todayAttendance->clock_out)
            <form action="{{ route('attendances.clock-out') }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 shadow-sm">
                    Absen Pulang Sekarang
                </button>
            </form>
        @endif
    </div>
</div>

@if(auth()->user()->isOwner())
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
    <form method="GET" action="{{ route('attendances.index') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Karyawan</label>
            <select name="user_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua Karyawan</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(request('user_id') == $emp->id)>{{ $emp->name }} ({{ $emp->role }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">Filter</button>
        <a href="{{ route('attendances.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-600 hover:bg-slate-50">Reset</a>
    </form>
</div>
@endif

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="text-base font-bold text-slate-900">Riwayat Kehadiran</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Tanggal</th>
                    <th class="px-5 py-3">Karyawan</th>
                    <th class="px-5 py-3">Jabatan</th>
                    <th class="px-5 py-3">Jam Masuk</th>
                    <th class="px-5 py-3">Jam Pulang</th>
                    <th class="px-5 py-3 text-right">Total Jam Kerja</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($attendances as $att)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $att->date->format('d/m/Y') }}</td>
                        <td class="px-5 py-3">{{ $att->user->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $att->user?->roleLabel() ?? '-' }}</td>
                        <td class="px-5 py-3 text-emerald-700 font-medium">{{ $att->clock_in?->format('H:i:s') ?? '-' }}</td>
                        <td class="px-5 py-3 text-amber-700 font-medium">{{ $att->clock_out?->format('H:i:s') ?? 'Belum Pulang' }}</td>
                        <td class="px-5 py-3 text-right font-semibold">{{ number_format($att->total_hours, 2) }} jam</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada catatan kehadiran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-200">
        {{ $attendances->links() }}
    </div>
</div>
@endsection

