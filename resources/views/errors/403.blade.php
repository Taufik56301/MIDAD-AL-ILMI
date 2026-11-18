<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Dilarang / Unauthorized</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-rose-200 p-8 text-center">
        <div class="mx-auto w-14 h-14 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-2xl font-bold mb-4">
            403
        </div>
        <h1 class="text-xl font-bold text-slate-900">Akses Dilarang / Unauthorized</h1>
        <p class="mt-2 text-sm text-slate-600">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman ini.' }}
        </p>
        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>

