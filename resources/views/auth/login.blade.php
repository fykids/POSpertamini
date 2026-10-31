@extends('layouts.app')

@section('title', 'Masuk - POS Pertamini')

@section('content')
    <div class="mx-auto max-w-md rounded-lg border bg-white p-6 shadow">
        <h1 class="mb-6 text-xl font-bold">Masuk POS Pertamini</h1>

        <form action="{{ route('login.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="mb-1 block text-sm font-medium">Nama pengguna</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}"
                    class="w-full rounded border p-2" required autofocus>
                @error('username')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Kata sandi</label>
                <input id="password" name="password" type="password" class="w-full rounded border p-2" required>
                @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full rounded bg-blue-600 py-2 font-bold text-white hover:bg-blue-700">Masuk</button>
        </form>
    </div>
@endsection