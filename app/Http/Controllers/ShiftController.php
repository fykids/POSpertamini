<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartShiftRequest;
use App\Contracts\D1ServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(protected D1ServiceInterface $d1) {}

    // Buka Shift Baru
    public function startShift(StartShiftRequest $request): RedirectResponse
    {
        try {
            $this->d1->startShift($request->session()->get('d1_user_id'), (float) $request->validated('total_price_per_liter'));

            return back()->with('success', 'Shift berhasil dibuka! Anda dapat mulai bertransaksi.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuka shift: '.$e->getMessage());
        }
    }

    // Tutup Shift (Rekonsiliasi Kas)
    public function closeShift(Request $request, int $shiftId): RedirectResponse
    {
        try {
            $this->d1->closeShift($shiftId, (int) $request->session()->get('d1_user_id'));

            return back()->with('success', 'Shift berhasil ditutup.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menutup shift: '.$e->getMessage());
        }
    }
}
