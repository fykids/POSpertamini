<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Contracts\D1ServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(protected D1ServiceInterface $d1) {}

    public function create(): View|RedirectResponse
    {
        if (session()->has('d1_user_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $username = $request->string('username')->trim()->toString();

        try {
            $user = $this->d1->authenticate($username);
        } catch (\Throwable $exception) {
            Log::error('Laravel login failed while calling the D1 bridge.', [
                'username' => $username,
                'exception' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);

            return back()->withInput($request->only('username'))->with('error', 'Layanan autentikasi sedang tidak tersedia.');
        }

        if (! $user || ! is_string($user['password'] ?? null) || ! Hash::check($request->string('password')->toString(), $user['password'])) {
            return back()->withInput($request->only('username'))->with('error', 'Nama pengguna atau kata sandi tidak valid.');
        }

        $request->session()->regenerate();
        $request->session()->put('d1_user_id', (int) $user['id']);
        $request->session()->put('d1_user_name', $user['name']);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
