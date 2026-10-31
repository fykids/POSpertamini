<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockRefillRequest;
use App\Contracts\D1ServiceInterface;
use Illuminate\Http\RedirectResponse;

class StockRefillController extends Controller
{
    public function __construct(protected D1ServiceInterface $d1) {}

    public function store(StoreStockRefillRequest $request): RedirectResponse
    {
        try {
            $this->d1->refillStock([
                'product_id' => $request->integer('product_id'),
                'liter_added' => (float) $request->input('liter_added'),
                'buying_price_per_liter' => (float) $request->input('buying_price_per_liter'),
                'supplier_note' => $request->string('supplier_note')->trim()->toString() ?: null,
            ]);

            return back()->with('success', 'Restok BBM berhasil dicatat.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal melakukan restok: '.$e->getMessage());
        }
    }
}
