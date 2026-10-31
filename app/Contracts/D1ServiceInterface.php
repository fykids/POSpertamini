<?php

namespace App\Contracts;

interface D1ServiceInterface
{
    public function products(): array;
    public function product(int $id): ?array;
    public function createProduct(array $payload): array;
    public function updateProduct(int $id, array $payload): array;
    public function deleteProduct(int $id): void;
    public function refillStock(array $payload): array;
    public function dashboardStats(): array;
    public function transactions(array $filters = []): array;
    public function expenses(array $filters = []): array;
    public function createExpense(array $payload): array;
    public function deleteExpense(int $id): void;
    public function financialReport(string $period): array;
    public function users(): array;
    public function createUser(array $payload): array;
    public function updateUser(int $id, array $payload): array;
    public function deleteUser(int $id): void;
    public function activeShift(int $userId): ?array;
    public function startShift(int $userId, float $totalPricePerLiter): array;
    public function closeShift(int $shiftId, int $userId): array;
    public function checkout(array $payload): array;
    public function authenticate(string $username): ?array;
}
