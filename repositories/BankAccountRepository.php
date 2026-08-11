<?php

namespace App\Repositories;

use App\Database;

final class BankAccountRepository
{
    public function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT * FROM bank_accounts ORDER BY nickname ASC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM bank_accounts WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO bank_accounts (nickname, bank_name, account_name, account_no_lak, account_no_usd, account_no_thb, swift_code)
             VALUES (:nickname, :bank_name, :account_name, :account_no_lak, :account_no_usd, :account_no_thb, :swift_code)
             RETURNING id'
        );
        $stmt->execute([
            'nickname' => $data['nickname'],
            'bank_name' => $data['bank_name'],
            'account_name' => $data['account_name'],
            'account_no_lak' => $data['account_no_lak'] ?? null,
            'account_no_usd' => $data['account_no_usd'] ?? null,
            'account_no_thb' => $data['account_no_thb'] ?? null,
            'swift_code' => $data['swift_code'] ?? null,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE bank_accounts SET
                nickname = :nickname,
                bank_name = :bank_name,
                account_name = :account_name,
                account_no_lak = :account_no_lak,
                account_no_usd = :account_no_usd,
                account_no_thb = :account_no_thb,
                swift_code = :swift_code,
                updated_at = now()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nickname' => $data['nickname'],
            'bank_name' => $data['bank_name'],
            'account_name' => $data['account_name'],
            'account_no_lak' => $data['account_no_lak'] ?? null,
            'account_no_usd' => $data['account_no_usd'] ?? null,
            'account_no_thb' => $data['account_no_thb'] ?? null,
            'swift_code' => $data['swift_code'] ?? null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM bank_accounts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
