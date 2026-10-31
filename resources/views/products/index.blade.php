@extends('layouts.app')
@section('title', 'Produk BBM')
@section('heading', 'Stok & Produk BBM')
@section('content')
<div class="grid gap-6 xl:grid-cols-3">
<form action="{{ route('products.store') }}" method="POST" class="rounded-2xl bg-white p-6 shadow-sm" id="product-form">@csrf
<h2 class="text-lg font-bold">Tambah Produk BBM</h2><p class="mt-1 text-sm text-slate-500">Modal awal otomatis dicatat sebagai riwayat pembelian stok.</p>
<div class="mt-5 space-y-4">
<label class="block text-sm font-medium">Nama BBM<input class="mt-1 w-full rounded-lg border p-2.5" name="name" value="{{ old('name') }}" placeholder="Pertalite" required></label>
<label class="block text-sm font-medium">Stok Awal (Liter)<input id="initial-stock" class="mt-1 w-full rounded-lg border p-2.5" name="current_stock" value="{{ old('current_stock', 0) }}" type="number" min="0" step="0.001" required></label>
<label class="block text-sm font-medium">Harga Modal Beli per Liter (Rp)<input id="buying-price" class="mt-1 w-full rounded-lg border p-2.5" name="buying_price_per_liter" value="{{ old('buying_price_per_liter') }}" type="number" min="0.01" step="0.01" required></label>
<label class="block text-sm font-medium">Harga Jual per Liter (Rp)<input id="selling-price" class="mt-1 w-full rounded-lg border p-2.5" name="price_per_liter" value="{{ old('price_per_liter') }}" type="number" min="0.01" step="0.01" required></label>
<label class="block text-sm font-medium">Kapasitas Tangki (Liter)<input class="mt-1 w-full rounded-lg border p-2.5" name="capacity" value="{{ old('capacity') }}" type="number" min="0.001" step="0.001" required></label>
<label class="block text-sm font-medium">Catatan Pembelian (opsional)<textarea class="mt-1 w-full rounded-lg border p-2.5" name="supplier_note" rows="2" placeholder="Nama pemasok / nomor nota">{{ old('supplier_note') }}</textarea></label>
</div>
<div class="mt-5 space-y-2 rounded-xl bg-slate-50 p-4 text-sm"><div class="flex justify-between"><span>Total Modal Pembelian</span><strong id="total-cost">Rp 0</strong></div><div class="flex justify-between"><span>Margin Laba Kotor / Liter</span><strong id="gross-margin">Rp 0</strong></div><div class="flex justify-between border-t pt-2"><span>Estimasi Total Laba Kotor</span><strong id="potential-profit" class="text-emerald-600">Rp 0</strong></div></div>
<button class="mt-5 w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-medium text-white">Simpan Produk & Pembelian Awal</button>
</form>
<div class="grid gap-4 md:grid-cols-2 xl:col-span-2">@forelse($products as $p)
@php($stock=(float)($p['current_stock']??0)) @php($capacity=max(.001,(float)($p['capacity']??0))) @php($hpp=(float)($p['buying_price_per_liter']??$p['cost_per_liter']??0))
<article class="rounded-2xl bg-white p-5 shadow-sm"><div class="flex items-start justify-between gap-3"><div><h2 class="font-bold">{{ $p['name'] }}</h2><p class="text-xs text-slate-500">HPP rata-rata berjalan</p></div><form method="POST" action="{{ route('products.destroy',$p['id']) }}">@csrf @method('DELETE')<button class="text-sm text-rose-600">Hapus</button></form></div>
<div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-emerald-500" style="width:{{ min(100,($stock/$capacity)*100) }}%"></div></div><p class="mt-2 text-sm font-medium">{{ number_format($stock,3,',','.') }} / {{ number_format($capacity,3,',','.') }} liter</p>
<div class="mt-4 grid grid-cols-2 gap-3 text-sm"><p>Harga jual<br><b>Rp {{ number_format($p['price_per_liter'],0,',','.') }}</b></p><p>HPP rata-rata<br><b>Rp {{ number_format($hpp,0,',','.') }}</b></p></div>
<details class="mt-5 rounded-lg bg-slate-50 p-3"><summary class="cursor-pointer font-medium text-slate-700">Refill / Isi Ulang Tangki</summary><form method="POST" action="{{ route('inventory.refill') }}" class="mt-3 space-y-2">@csrf<input type="hidden" name="product_id" value="{{ $p['id'] }}"><input class="w-full rounded border p-2" name="liter_added" type="number" min="0.001" step="0.001" placeholder="Liter ditambahkan" required><input class="w-full rounded border p-2" name="buying_price_per_liter" type="number" min="0.01" step="0.01" placeholder="Modal beli baru / liter" required><input class="w-full rounded border p-2" name="supplier_note" placeholder="Catatan pemasok / nota"><button class="w-full rounded bg-slate-800 px-3 py-2 text-white">Simpan Refill & Hitung HPP Baru</button></form></details></article>
@empty<div class="rounded-2xl bg-white p-8 text-slate-500 shadow-sm">Belum ada produk.</div>@endforelse</div></div>
<script>(()=>{const n=id=>Number(document.getElementById(id)?.value||0)||0;const m=v=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v||0);const calc=()=>{const s=n('initial-stock'),b=n('buying-price'),p=n('selling-price'),g=p-b;document.getElementById('total-cost').textContent=m(s*b);document.getElementById('gross-margin').textContent=m(g);document.getElementById('potential-profit').textContent=m(s*g)};['initial-stock','buying-price','selling-price'].forEach(id=>document.getElementById(id)?.addEventListener('input',calc));calc()})();</script>
@endsection
