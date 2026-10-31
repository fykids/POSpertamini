@extends('layouts.app') @section('title', 'Dashboard') @section('heading', 'Dashboard Penjualan') @section('content')
@php($cards = [['Penjualan Hari Ini', $stats['sales']['amount'] ?? 0, 'Rp'], ['Liter Terjual', $stats['sales']['liters'] ?? 0, 'L'], ['Total Modal', $stats['cost'] ?? 0, 'Rp'], ['Estimasi Laba', $stats['profit'] ?? 0, 'Rp']])
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">@foreach($cards as [$label, $value, $prefix])
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">{{ $label }}</p>
        <p class="mt-2 text-2xl font-bold">{{ $prefix }} {{ number_format($value, 0, ',', '.') }}</p>
</div>@endforeach
</div>
<div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-wrap justify-between gap-3">
        <h2 class="font-bold">Riwayat Penjualan</h2>
        <form class="flex gap-2"><input class="rounded border p-2" name="search" value="{{ request('search') }}"
                placeholder="Invoice / produk"><input class="rounded border p-2" name="date" type="date"
                value="{{ request('date') }}"><button class="rounded bg-slate-900 px-3 text-white">Filter</button>
        </form>
    </div>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b text-slate-500">
                <tr>
                    <th>Invoice</th>
                    <th>Produk</th>
                    <th>Liter</th>
                    <th>Nominal</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>@forelse($transactions['data'] as $t)
                <tr class="border-b">
                    <td class="py-3 font-medium">{{ $t['invoice_number'] }}</td>
                    <td>{{ $t['product_name'] }}</td>
                    <td>{{ $t['liter_qty'] }} L</td>
                    <td>Rp {{ number_format($t['total_amount'], 0, ',', '.') }}</td>
                    <td>{{ $t['created_at'] }}</td>
            </tr>@empty<tr>
                    <td colspan="5" class="py-6 text-center text-slate-500">Belum ada transaksi.</td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-4 text-sm text-slate-500">Total {{ $transactions['meta']['total'] ?? 0 }} transaksi · halaman
        {{ $transactions['meta']['page'] ?? 1 }}</p>
</div>
@endsection