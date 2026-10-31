<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Contracts\D1ServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function __construct(protected D1ServiceInterface $d1) {}

    // Halaman Menu Jual
    public function index(Request $request): View
    {
        try {
            $userId = (int) $request->session()->get('d1_user_id');
            $products = $this->d1->products();
            $activeShift = $this->d1->activeShift($userId);
        } catch (\Throwable $e) {
            return view('jual.index', ['products' => [], 'activeShift' => null])
                ->with('error', $e->getMessage());
        }

        return view('jual.index', compact('products', 'activeShift'));
    }

    // Proses Transaksi
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $invoice = 'INV-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));

        try {
            $transaction = $this->d1->checkout([
                'invoice' => $invoice,
                'shift_id' => $validated['shift_id'],
                'product_id' => $validated['product_id'],
                'user_id' => (int) $request->session()->get('d1_user_id'),
                'type' => $validated['type'],
                'value' => (float) $validated['value'],
            ]);

            return back()->with('success', "Transaksi Berhasil! Invoice: {$transaction['invoice']}");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
