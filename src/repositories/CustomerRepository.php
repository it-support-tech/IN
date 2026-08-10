<?php

namespace App\Repositories;

use App\Database;
use PDO;

final class CustomerRepository
{
    public function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT * FROM customers ORDER BY code ASC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM customers WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM customers WHERE code = :code'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO customers (code, company_name, village, district, province, tax_id)
             VALUES (:code, :company_name, :village, :district, :province, :tax_id)
             RETURNING id'
        );
        $stmt->execute([
            'code' => $data['code'],
            'company_name' => $data['company_name'],
            'village' => $data['village'] ?? null,
            'district' => $data['district'] ?? null,
            'province' => $data['province'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE customers SET
                code = :code,
                company_name = :company_name,
                village = :village,
                district = :district,
                province = :province,
                tax_id = :tax_id,
                updated_at = now()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'code' => $data['code'],
            'company_name' => $data['company_name'],
            'village' => $data['village'] ?? null,
            'district' => $data['district'] ?? null,
            'province' => $data['province'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
        ]);
    }
}
