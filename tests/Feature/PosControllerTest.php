<?php

use App\Services\D1Service;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('redirects an unauthenticated cashier to the D1 login page', function () {
    $this->get(route('jual.index'))->assertRedirect(route('login'));
});

it('starts a D1-backed cashier session after valid credentials', function () {
    config()->set('services.d1.url', 'https://bridge.example');
    config()->set('services.d1.secret', 'test-secret');
    Http::preventStrayRequests();
    Http::fake([
        'https://bridge.example/api/auth/login' => Http::response([
            'success' => true,
            'user' => ['id' => 7, 'name' => 'kasir', 'password' => Hash::make('rahasia123')],
        ]),
    ]);

    $this->post(route('login.store'), ['username' => 'kasir', 'password' => 'rahasia123'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('d1_user_id', 7)
        ->assertSessionHas('d1_user_name', 'kasir');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://bridge.example/api/auth/login'
            && $request->header('Authorization')[0] === 'Bearer test-secret'
            && $request['username'] === 'kasir';
    });
});

it('logs the HTTP status and body when D1 authentication fails', function () {
    config()->set('services.d1.url', 'https://bridge.example');
    config()->set('services.d1.secret', 'test-secret');
    Http::preventStrayRequests();
    Http::fake([
        'https://bridge.example/api/auth/login' => Http::response([
            'success' => false,
            'message' => 'Endpoint tidak ditemukan.',
        ], 404),
    ]);
    Log::spy();

    $exception = null;

    try {
        app(D1Service::class)->authenticate('kasir');
    } catch (RuntimeException $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeInstanceOf(RuntimeException::class);
    Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'D1 authentication request returned an HTTP error.'
            && $context['status'] === 404
            && $context['body'] === '{"success":false,"message":"Endpoint tidak ditemukan."}';
    });
});

it('sends an authenticated checkout request to the D1 bridge', function () {
    config()->set('services.d1.url', 'https://bridge.example');
    config()->set('services.d1.secret', 'test-secret');
    Http::preventStrayRequests();
    Http::fake([
        'https://bridge.example/api/pos/checkout' => Http::response([
            'success' => true,
            'data' => ['invoice' => 'INV-20261030-ABC123', 'totalAmount' => 20000],
        ], 201),
    ]);

    $transaction = app(D1Service::class)->checkout([
        'invoice' => 'INV-20261030-ABC123',
        'shift_id' => 3,
        'product_id' => 2,
        'user_id' => 7,
        'type' => 'RUPIAH',
        'value' => 20000.0,
    ]);

    expect($transaction)->toBe(['invoice' => 'INV-20261030-ABC123', 'totalAmount' => 20000]);
    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://bridge.example/api/pos/checkout'
            && $request->header('Authorization')[0] === 'Bearer test-secret'
            && $request['userId'] === 7
            && $request['type'] === 'RUPIAH';
    });
});

it('surfaces a D1 bridge domain error', function () {
    config()->set('services.d1.url', 'https://bridge.example');
    config()->set('services.d1.secret', 'test-secret');
    Http::preventStrayRequests();
    Http::fake([
        'https://bridge.example/api/inventory/refill' => Http::response([
            'success' => false,
            'message' => 'Pengisian melebihi kapasitas tangki.',
        ], 409),
    ]);

    app(D1Service::class)->refillStock([
        'product_id' => 2,
        'liter_added' => 20.0,
        'buying_price_per_liter' => 10000.0,
    ]);
})->throws(RuntimeException::class, 'Pengisian melebihi kapasitas tangki.');
