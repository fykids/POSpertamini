<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Pertamini POS')</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-slate-50 text-slate-800">
    <div class="min-h-screen lg:flex">
        <aside class="bg-slate-950 text-slate-300 w-full lg:w-64 p-5"><a href="{{ route('dashboard') }}"
                class="text-xl font-bold text-white">PERTAMINI <span class="text-emerald-400">POS</span></a>
            <p class="mt-1 text-xs text-slate-500">Operasional transparan</p>
            <nav class="mt-8 space-y-2">
                @foreach(['dashboard' => 'Dashboard', 'jual.index' => 'Menu Jual (POS)', 'products.index' => 'Stok & Produk BBM', 'expenses.index' => 'Pengeluaran', 'reports.financial' => 'Laporan Keuangan'] as $route => $label)<a
                    href="{{ route($route) }}"
                class="block rounded-xl px-4 py-3 text-sm {{ request()->routeIs($route) ? 'bg-emerald-500 text-white' : 'hover:bg-slate-800' }}">{{ $label }}</a>@endforeach
            </nav>
        </aside>
        <section class="flex-1">
            <header class="flex items-center justify-between bg-white px-6 py-4 shadow-sm">
                <div>
                    <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
                    <h1 class="font-semibold">@yield('heading', 'Dashboard')</h1>
                </div><button id="settingsButton" class="rounded-full bg-slate-100 p-3"
                    aria-label="Pengaturan">⚙</button>
            </header>
            <main class="p-5 lg:p-8">
                @yield('content')
            </main>
        </section>
    </div>
    <x-toast-alert />
    <div id="drawer"
        class="fixed inset-y-0 right-0 z-50 hidden w-full max-w-md overflow-y-auto bg-white p-6 shadow-2xl">
        <div class="flex justify-between">
            <h2 class="text-xl font-bold">Pengaturan Admin</h2><button id="closeDrawer">✕</button>
        </div>
        <p class="mt-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">API Bridge D1: terhubung melalui
            konfigurasi Laravel.</p>
        <form class="mt-6 space-y-3" method="POST" action="{{ route('users.store') }}">@csrf<h3 class="font-semibold">
                Tambah akun kasir/admin</h3><input class="w-full rounded border p-2" name="name" placeholder="Username"
                required><input class="w-full rounded border p-2" name="password" type="password"
                placeholder="Password minimal 8 karakter" required><select class="w-full rounded border p-2"
                name="role">
                <option value="cashier">Kasir</option>
                <option value="admin">Admin</option>
            </select><button class="rounded bg-slate-900 px-4 py-2 text-white">Simpan akun</button></form>
        <form class="mt-8" method="POST" action="{{ route('logout') }}">@csrf<button class="text-rose-600">Keluar dari
                aplikasi</button></form>
    </div>
    <script>const d = document.getElementById('drawer'); document.getElementById('settingsButton').onclick = () => d.classList.remove('hidden'); document.getElementById('closeDrawer').onclick = () => d.classList.add('hidden')</script>
</body>

</html>
