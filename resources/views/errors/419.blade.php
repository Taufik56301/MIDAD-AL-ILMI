<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Sesi Formulir Berakhir</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-amber-200 p-8 text-center">
        <div class="mx-auto w-14 h-14 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-2xl font-bold mb-4">
            419
        </div>
        <h1 class="text-xl font-bold text-slate-900">Sesi Halaman Berakhir (Page Expired)</h1>
        <p class="mt-2 text-sm text-slate-600">
            Token keamanan formulir (CSRF) sudah tidak berlaku karena halaman terlalu lama terbuka, berpindah tab/sesi, atau memakai tombol Back browser.
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                Muat Ulang / Halaman Login
            </a>
            <button type="button" onclick="window.location.href=document.referrer || '{{ route('dashboard') }}'" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">
                Kembali
            </button>
        </div>
    </div>
</body>
</html>

