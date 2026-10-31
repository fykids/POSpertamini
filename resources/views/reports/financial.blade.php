@extends('layouts.app') @section('title', 'Laporan Keuangan') @section('heading', 'Laporan Keuangan') @section('content')
    <form class="mb-5 flex gap-2"><select name="period" class="rounded border p-2">
            <option value="today" @selected($period === 'today')>Hari ini</option>
            <option value="week" @selected($period === 'week')>Minggu ini</option>
            <option value="month" @selected($period === 'month')>Bulan ini</option>
        </select><button class="rounded bg-slate-900 px-4 text-white">Terapkan</button></form>
    <div class="grid gap-4 md:grid-cols-4">
        @foreach([['Pemasukan', $report['income'], 'emerald'], ['Operasional', $report['expenses'], 'rose'], ['Modal BBM', $report['capital'], 'amber'], ['Laba/Rugi Bersih', $report['net_profit'], 'blue']] as [$name, $amount, $color])
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $name }}</p>
                <p class="mt-2 text-xl font-bold text-{{ $color }}-600">Rp {{ number_format($amount, 0, ',', '.') }}</p>
        </div>@endforeach
    </div>
    <div class="mt-6 rounded-2xl bg-white p-5 shadow-sm">
        <h2 class="font-bold">Rekap Arus Kas</h2>
        <table class="mt-4 w-full text-sm">
            <thead>
                <tr>
                    <th class="text-left">Tanggal</th>
                    <th class="text-left">Jenis</th>
                    <th class="text-left">Catatan</th>
                    <th class="text-right">Nilai</th>
                </tr>
            </thead>
            <tbody>@forelse($report['flows'] as $flow)
                <tr class="border-t">
                    <td class="py-3">{{ $flow['date'] }}</td>
                    <td>{{ $flow['type'] }}</td>
                    <td>{{ $flow['note'] }}</td>
                    <td class="text-right {{ $flow['amount'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">Rp
                        {{ number_format($flow['amount'], 0, ',', '.') }}</td>
            </tr>@empty<tr>
                    <td colspan="4" class="py-6 text-center text-slate-500">Belum ada arus kas pada periode ini.</td>
                </tr>@endforelse
            </tbody>
        </table>
</div>@endsection