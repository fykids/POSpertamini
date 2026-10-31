@extends('layouts.app')

@section('title', 'Menu Jual - POS Pertamini')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- PANEL 1: Informasional Shift & Tangki BBM -->
            <div class="space-y-6">

                <!-- Card Status Shift -->
                <div class="bg-white p-5 rounded-lg shadow border">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Status Shift Kasir</h3>

                    @if(blank($activeShift))
                        <p class="text-sm text-yellow-600 mb-4">Belum ada shift aktif. Silakan buka shift untuk mulai melayani
                            transaksi.</p>
                        <form action="{{ route('shift.start') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Harga Patokan per Liter
                                    (Rp)</label>
                                <input type="number" name="total_price_per_liter" value="13000"
                                    class="w-full border rounded p-2 text-sm" required>
                            </div>
                            <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                                Buka Shift Sekarang
                            </button>
                        </form>
                    @else
                        <div class="space-y-2 text-sm mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">ID Shift:</span>
                                <span class="font-bold">#{{ $activeShift['id'] ?? '' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Ekspektasi Kas:</span>
                                <span class="font-bold text-green-600">Rp
                                    {{ number_format($activeShift['total_cash_expected'] ?? 0) }}</span>
                            </div>
                        </div>
                        <form action="{{ route('shift.close', $activeShift['id'] ?? 0) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-150">
                                Tutup Shift
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Card Informasi Stok Tangki -->
                <div class="bg-white p-5 rounded-lg shadow border">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Stok Tangki Fisik</h3>
                    <div class="space-y-3">
                        @forelse($products as $product)
                            <div>
                                <div class="flex justify-between text-sm font-semibold mb-1">
                                    <span>{{ $product['name'] }}</span>
                                    <span>{{ $product['current_stock'] }} / {{ $product['capacity'] }} L</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full"
                                        style="width: {{ $product['capacity'] > 0 ? min(100, ($product['current_stock'] / $product['capacity']) * 100) : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada data produk tangki BBM.</p>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- PANEL 2: Form Input Transaksi POS -->
            <div class="md:col-span-2 bg-white p-6 rounded-lg shadow border">
                <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-6">Form Transaksi Pengisian BBM</h2>

                <form action="{{ route('jual.store') }}" method="POST" class="space-y-5">
                    @csrf

                    @if(!empty($activeShift))
                        <input type="hidden" name="shift_id" value="{{ $activeShift['id'] ?? '' }}">
                    @endif

                    <!-- Pilih Produk BBM -->
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">Jenis BBM</label>
                        <select name="product_id" class="w-full border border-gray-300 rounded-lg p-3 text-gray-800" {{ blank($activeShift) ? 'disabled' : '' }} required>
                            @foreach($products as $product)
                                <option value="{{ $product['id'] }}">
                                    {{ $product['name'] }} — Rp {{ number_format($product['price_per_liter']) }}/Liter
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tipe Pengisian (Rupiah / Liter) -->
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">Metode Input Pengisian</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="border rounded-lg p-3 flex items-center gap-2 cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="type" value="RUPIAH" checked {{ blank($activeShift) ? 'disabled' : '' }}>
                                <span class="font-semibold text-sm">Nominal Uang (Rp)</span>
                            </label>
                            <label class="border rounded-lg p-3 flex items-center gap-2 cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="type" value="LITER" {{ blank($activeShift) ? 'disabled' : '' }}>
                                <span class="font-semibold text-sm">Volume (Liter)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Input Nominal / Volume -->
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">Nilai / Jumlah</label>
                        <input type="number" step="0.001" name="value" placeholder="Contoh: 20000 atau 2.5"
                            class="w-full border border-gray-300 rounded-lg p-3 text-lg font-bold text-gray-900" {{ blank($activeShift) ? 'disabled' : '' }} required>
                    </div>

                    <!-- Tombol Submit -->
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-lg shadow-md transition duration-150 disabled:opacity-50"
                        {{ blank($activeShift) ? 'disabled' : '' }}>
                        Proses Transaksi & Cetak
                    </button>
                </form>
            </div>

        </div>

    </div>
@endsection
