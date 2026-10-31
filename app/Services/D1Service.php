<?php

namespace App\Services;

use App\Contracts\D1ServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class D1Service implements D1ServiceInterface
{
    private readonly ?string $baseUrl;

    private readonly ?string $secret;

    public function __construct()
    {
        $this->baseUrl = config('services.d1.url');
        $this->secret = config('services.d1.secret');
    }

    private function client(): PendingRequest
    {
        if (blank($this->baseUrl) || blank($this->secret)) {
            throw new RuntimeException('D1 bridge belum dikonfigurasi. Atur D1_BRIDGE_URL dan D1_BRIDGE_SECRET.');
        }

        return Http::withoutVerifying()
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->withHeaders(['Authorization' => "Bearer {$this->secret}"])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(10);
    }

    /** @return array<int, array<string, mixed>> */
    public function products(): array
    {
        $result = $this->data($this->client()->get('/api/products'));

        return is_array($result) ? $result : [];
    }

    public function product(int $id): ?array
    {
        return $this->data($this->client()->get("/api/products/{$id}"));
    }
    public function createProduct(array $payload): array
    {
        return $this->data($this->client()->post('/api/products', [
            'name' => (string) $payload['name'],
            'price_per_liter' => (float) $payload['price_per_liter'],
            'buying_price_per_liter' => (float) ($payload['buying_price_per_liter'] ?? 0),
            'current_stock' => (float) ($payload['current_stock'] ?? 0),
            'capacity' => (float) $payload['capacity'],
            'supplier_note' => (string) ($payload['supplier_note'] ?? ''),
        ]));
    }
    public function updateProduct(int $id, array $payload): array
    {
        return $this->data($this->client()->put("/api/products/{$id}", $payload));
    }
    public function deleteProduct(int $id): void
    {
        $this->data($this->client()->delete("/api/products/{$id}"));
    }
    public function dashboardStats(): array
    {
        return $this->data($this->client()->get('/api/dashboard/stats'));
    }
    public function transactions(array $filters = []): array
    {
        return $this->data($this->client()->get('/api/transactions', $filters));
    }
    public function expenses(array $filters = []): array
    {
        return $this->data($this->client()->get('/api/expenses', $filters));
    }
    public function createExpense(array $payload): array
    {
        return $this->data($this->client()->post('/api/expenses', $payload));
    }
    public function deleteExpense(int $id): void
    {
        $this->data($this->client()->delete("/api/expenses/{$id}"));
    }
    public function financialReport(string $period): array
    {
        return $this->data($this->client()->get('/api/reports/financial', ['period' => $period]));
    }
    public function users(): array
    {
        return $this->data($this->client()->get('/api/users'));
    }
    public function createUser(array $payload): array
    {
        return $this->data($this->client()->post('/api/users', $payload));
    }
    public function updateUser(int $id, array $payload): array
    {
        return $this->data($this->client()->put("/api/users/{$id}", $payload));
    }
    public function deleteUser(int $id): void
    {
        $this->data($this->client()->delete("/api/users/{$id}"));
    }

    /** @return array<string, mixed>|null */
    public function authenticate(string $username): ?array
    {
        try {
            $response = $this->client()->post('/api/auth/login', ['username' => $username]);
        } catch (ConnectionException $exception) {
            Log::error('D1 authentication request failed before receiving a response.', [
                'url' => rtrim((string) $this->baseUrl, '/') . '/api/auth/login',
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('D1 bridge tidak dapat dihubungi.', previous: $exception);
        }

        if ($response->failed()) {
            Log::error('D1 authentication request returned an HTTP error.', [
                'url' => rtrim((string) $this->baseUrl, '/') . '/api/auth/login',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                $response->json('message') ?? 'D1 bridge menolak permintaan autentikasi.',
                $response->status(),
            );
        }

        if ($response->json('success') !== true) {
            Log::error('D1 authentication response has an invalid contract.', [
                'url' => rtrim((string) $this->baseUrl, '/') . '/api/auth/login',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException($response->json('message') ?? 'Respons autentikasi D1 tidak valid.');
        }

        $user = $response->json('user');

        return is_array($user) ? $user : null;
    }

    /** @return array<string, mixed>|null */
    public function activeShift(int $userId): ?array
    {
        return $this->data($this->client()->get('/api/shifts/open', ['userId' => $userId]));
    }

    /** @return array<string, mixed> */
    public function startShift(int $userId, float $totalPricePerLiter): array
    {
        return $this->data($this->client()->post('/api/shifts', ['userId' => $userId, 'totalPricePerLiter' => $totalPricePerLiter]));
    }

    /** @return array<string, mixed> */
    public function closeShift(int $shiftId, int $userId): array
    {
        return $this->data($this->client()->post("/api/shifts/{$shiftId}/close", ['userId' => $userId]));
    }

    /** @param array{invoice: string, shift_id: int, product_id: int, user_id: int, type: string, value: float} $payload
     * @return array<string, mixed>
     */
    public function checkout(array $payload): array
    {
        return $this->data($this->client()->post('/api/pos/checkout', ['invoice' => $payload['invoice'], 'shiftId' => $payload['shift_id'], 'productId' => $payload['product_id'], 'userId' => $payload['user_id'], 'type' => $payload['type'], 'value' => $payload['value']]));
    }

    /** @param array{product_id: int, liter_added: float, buying_price_per_liter: float, supplier_note?: string|null} $payload
     * @return array<string, mixed>
     */
    public function refillStock(array $payload): array
    {
        return $this->data($this->client()->post('/api/inventory/refill', ['productId' => $payload['product_id'], 'literAdded' => $payload['liter_added'], 'buyingPricePerLiter' => $payload['buying_price_per_liter'], 'supplierNote' => $payload['supplier_note'] ?? null]));
    }

    private function data(Response $response): mixed
    {
        if ($response->successful() && $response->json('success') === true) {
            return $response->json('data');
        }
        throw new RuntimeException($response->json('message') ?? 'D1 bridge tidak dapat memproses permintaan.', $response->status());
    }
}
