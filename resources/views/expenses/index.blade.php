@extends('layouts.app') @section('title', 'Pengeluaran') @section('heading', 'Pengeluaran Operasional')
@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3 rounded-2xl bg-white p-5 shadow-sm">
            @csrf<h2 class="font-bold">Catat pengeluaran</h2><input class="w-full rounded border p-2" name="date"
                type="date" value="{{ now()->toDateString() }}" required><input class="w-full rounded border p-2"
                name="category" placeholder="Listrik, gaji, perawatan" required><input class="w-full rounded border p-2"
                type="number" name="amount" placeholder="Nominal" required><textarea class="w-full rounded border p-2"
                name="note" placeholder="Keterangan"></textarea><button
                class="rounded bg-rose-600 px-4 py-2 text-white">Simpan</button></form>
        <div class="lg:col-span-2 rounded-2xl bg-white p-5 shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Nominal</th>
                        <th>Note</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>@foreach($expenses as $e)
                    <tr class="border-t">
                        <td class="py-3">{{ $e['date'] }}</td>
                        <td>{{ $e['category'] }}</td>
                        <td>Rp {{ number_format($e['amount'], 0, ',', '.') }}</td>
                        <td>{{ $e['note'] }}</td>
                        <td>
                            <form method="POST" action="{{ route('expenses.destroy', $e['id']) }}">@csrf
                                @method('DELETE')<button class="text-rose-600">Hapus</button></form>
                        </td>
                </tr>@endforeach
                </tbody>
            </table>
        </div>
</div>@endsection