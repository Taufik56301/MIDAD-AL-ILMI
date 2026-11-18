<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>@yield('title', 'SIM Percetakan Midad Al-Ilmi')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<div class="min-h-screen flex flex-col lg:flex-row">
    {{-- Sidebar --}}
    <aside class="w-full lg:w-64 bg-slate-900 text-slate-100 flex flex-col shrink-0">
        <div class="px-5 py-5 border-b border-slate-800 flex items-center justify-between">
            <div>
                <div class="text-lg font-bold tracking-tight text-white">Midad Al-Ilmi</div>
                <div class="text-xs text-slate-400">SIM Operasional Percetakan</div>
            </div>
            <button type="button" onclick="document.getElementById('sidebar-nav').classList.toggle('hidden')" class="lg:hidden px-2.5 py-1.5 rounded bg-slate-800 text-xs text-slate-200">
                Menu
            </button>
        </div>

        <div id="sidebar-nav" class="hidden lg:flex flex-col flex-1 justify-between p-4 space-y-6">
            <nav class="space-y-1 text-sm">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('attendances.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('attendances.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Absensi Harian</span>
                </a>
                <a href="{{ route('production.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('production.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Antrean Produksi</span>
                </a>

                @if(auth()->user()->hasRole('admin', 'owner'))
                    <div class="pt-3 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Front Office & Kasir</div>
                    <a href="{{ route('customers.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('customers.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>Pelanggan (CRM)</span>
                    </a>
                    <a href="{{ route('orders.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('orders.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>Pesanan (Order Entry)</span>
                    </a>
                    <a href="{{ route('payments.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('payments.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>Kasir & Pembayaran</span>
                    </a>
                @endif

                <div class="pt-3 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Gudang & Manajemen</div>
                <a href="{{ route('materials.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('materials.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>Stok Bahan Baku</span>
                </a>

                @if(auth()->user()->isOwner())
                    <a href="{{ route('reports.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>Laporan & Ekspor</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white font-medium' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>Data Karyawan</span>
                    </a>
                @endif
            </nav>

            <div class="border-t border-slate-800 pt-4">
                <div class="px-3 py-2 rounded-lg bg-slate-800/70 mb-3">
                    <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-indigo-300">{{ auth()->user()->roleLabel() }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-3 py-2 rounded-lg text-left text-sm text-rose-300 hover:bg-rose-950/50 hover:text-rose-200 transition">
                        Keluar (Logout)
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900">@yield('header', 'Dashboard')</h1>
                @hasSection('subheader')
                    <p class="text-xs text-slate-500 mt-0.5">@yield('subheader')</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @yield('header-actions')
                <span class="text-xs text-slate-500 hidden sm:inline">{{ now()->translatedFormat('d F Y, H:i') }} WIB</span>
            </div>
        </header>

        <main class="flex-1 p-6 space-y-5">
            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 font-medium">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('insufficient_stock'))
                <div class="rounded-xl border-2 border-rose-400 bg-rose-50 p-4 text-sm text-rose-900">
                    <div class="font-bold text-rose-700 mb-1">Peringatan Stok Tidak Mencukupi (Insufficient Stock Alert)</div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach(session('insufficient_stock') as $alert)
                            <li>{{ $alert }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($errors->any() && !session('insufficient_stock'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <div class="font-semibold mb-1">Terjadi kesalahan input:</div>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>

