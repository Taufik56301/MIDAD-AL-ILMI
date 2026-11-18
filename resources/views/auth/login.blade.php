<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Percetakan Midad Al-Ilmi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
<div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 space-y-6">
    <div class="text-center">
        <div class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold mb-3">
            Sistem Informasi Back-Office
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Percetakan Midad Al-Ilmi</h1>
        <p class="text-sm text-slate-500 mt-1">Silakan masuk menggunakan akun karyawan Anda</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                   placeholder="nama@midad.test">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Kata Sandi</label>
            <input id="password" name="password" type="password" required
                   class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                   placeholder="••••••••">
        </div>

        <label class="inline-flex items-center gap-2 text-xs text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600">
            Ingat sesi saya
        </label>

        <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
            Masuk ke Sistem
        </button>
    </form>

    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3.5 text-xs text-slate-600 space-y-1.5">
        <div class="font-semibold text-slate-800">Akun Demo (Kata sandi: <code class="px-1 py-0.5 bg-slate-200 rounded">password</code>)</div>
        <div class="flex flex-wrap gap-1.5">
            <button type="button" onclick="fillDemo('owner@midad.test')" class="px-2 py-1 rounded bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700">Owner: owner@midad.test</button>
            <button type="button" onclick="fillDemo('admin@midad.test')" class="px-2 py-1 rounded bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700">Admin: admin@midad.test</button>
            <button type="button" onclick="fillDemo('operator@midad.test')" class="px-2 py-1 rounded bg-white border border-slate-300 hover:bg-indigo-50 text-slate-700">Operator: operator@midad.test</button>
        </div>
    </div>
</div>
<script>
function fillDemo(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password';
}
</script>
</body>
</html>

