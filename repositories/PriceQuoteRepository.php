<?php

namespace App\Repositories;

use App\Database;
use PDO;

final class PriceQuoteRepository
{
    /**
     * Doc number format: YYYYMM + '00' + a running number that resets each
     * month (e.g. 20260800001 for the 1st statement issued in August 2026).
     */
    public function suggestNextDocNo(): string
    {
        $prefix = date('Ym') . '00';

        $stmt = Database::connection()->prepare(
            "SELECT doc_no FROM price_quotes
             WHERE doc_no LIKE :like
             ORDER BY (substr(doc_no, :from))::bigint DESC LIMIT 1"
        );
        // substring(doc_no FROM :from) is Postgres's *regex-extraction* form
        // (two-arg "FROM" syntax matches a POSIX pattern, not a start
        // position) — it silently returned NULL for every row, so the sort
        // was unordered and "next number" could go backwards. substr() with
        // comma syntax is unambiguous.
        $stmt->execute([
            'like' => $prefix . '%',
            'from' => strlen($prefix) + 1,
        ]);
        $last = $stmt->fetchColumn();

        if (!$last) {
            return $prefix . '001';
        }

        $suffix = substr($last, strlen($prefix));
        $width = strlen($suffix);
        $next = (int) $suffix + 1;

        return $prefix . str_pad((string) $next, $width, '0', STR_PAD_LEFT);
    }

    /**
     * Creates a price statement + its line items in a single transaction.
     * All computed fields (price after discount, USD price, total amount)
     * are taken directly from staff input, not recomputed server-side —
     * same trust-the-input rule used for invoices.
     */
    public function create(array $quoteData, array $items): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO price_quotes (doc_no, customer_id, doc_date, currency, total_usd, remark, rate_currency, header_lang)
                 VALUES (:doc_no, :customer_id, :doc_date, :currency, :total_usd, :remark, :rate_currency, :header_lang)
                 RETURNING id'
            );
            $stmt->execute([
                'doc_no' => $quoteData['doc_no'],
                'customer_id' => !empty($quoteData['customer_id']) ? (int) $quoteData['customer_id'] : null,
                'doc_date' => $quoteData['doc_date'],
                'currency' => $quoteData['currency'] ?? 'USD',
                'total_usd' => (float) ($quoteData['total_usd'] ?? 0),
                'remark' => $quoteData['remark'] ?? null,
                'rate_currency' => $quoteData['rate_currency'] ?? 'USD',
                'header_lang' => $quoteData['header_lang'] ?? 'zh',
            ]);
            $quoteId = (int) $stmt->fetchColumn();

            $this->insertItems($pdo, $quoteId, $items);

            $pdo->commit();
            return $quoteId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $quoteData, array $items): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE price_quotes SET
                    doc_no = :doc_no,
                    customer_id = :customer_id,
                    doc_date = :doc_date,
                    currency = :currency,
                    total_usd = :total_usd,
                    remark = :remark,
                    rate_currency = :rate_currency,
                    header_lang = :header_lang
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'doc_no' => $quoteData['doc_no'],
                'customer_id' => !empty($quoteData['customer_id']) ? (int) $quoteData['customer_id'] : null,
                'doc_date' => $quoteData['doc_date'],
                'currency' => $quoteData['currency'] ?? 'USD',
                'total_usd' => (float) ($quoteData['total_usd'] ?? 0),
                'remark' => $quoteData['remark'] ?? null,
                'rate_currency' => $quoteData['rate_currency'] ?? 'USD',
                'header_lang' => $quoteData['header_lang'] ?? 'zh',
            ]);

            $pdo->prepare('DELETE FROM price_quote_items WHERE quote_id = :id')->execute(['id' => $id]);
            $this->insertItems($pdo, $id, $items);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function insertItems(PDO $pdo, int $quoteId, array $items): void
    {
        $itemStmt = $pdo->prepare(
            'INSERT INTO price_quote_items
                (quote_id, line_no, quantity_liters, structure_price, discount, price_after_discount, exchange_rate, usd_price, total_amount, remark)
             VALUES
                (:quote_id, :line_no, :quantity_liters, :structure_price, :discount, :price_after_discount, :exchange_rate, :usd_price, :total_amount, :remark)'
        );
        foreach ($items as $i => $item) {
            $itemStmt->execute([
                'quote_id' => $quoteId,
                'line_no' => $i + 1,
                'quantity_liters' => (float) ($item['quantity_liters'] ?? 0),
                'structure_price' => (float) ($item['structure_price'] ?? 0),
                'discount' => (float) ($item['discount'] ?? 0),
                'price_after_discount' => (float) ($item['price_after_discount'] ?? 0),
                'exchange_rate' => (float) ($item['exchange_rate'] ?? 0),
                'usd_price' => (float) ($item['usd_price'] ?? 0),
                'total_amount' => (float) ($item['total_amount'] ?? 0),
                'remark' => $item['remark'] ?? null,
            ]);
        }
    }

    public function findWithDetails(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT q.*, c.code AS customer_code, c.company_name, c.village, c.district, c.province, c.tax_id
             FROM price_quotes q
             LEFT JOIN customers c ON c.id = q.customer_id
             WHERE q.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $quote = $stmt->fetch();
        if (!$quote) {
            return null;
        }

        $itemStmt = $pdo->prepare(
            'SELECT * FROM price_quote_items WHERE quote_id = :id ORDER BY line_no ASC'
        );
        $itemStmt->execute(['id' => $id]);
        $quote['items'] = $itemStmt->fetchAll();

        return $quote;
    }

    public function listHistory(?string $search = null): array
    {
        $sql = 'SELECT q.id, q.doc_no, q.doc_date, q.currency,
                       c.code AS customer_code, c.company_name
                FROM price_quotes q
                LEFT JOIN customers c ON c.id = q.customer_id';
        $params = [];

        if ($search) {
            $sql .= ' WHERE q.doc_no ILIKE :search OR c.company_name ILIKE :search OR c.code ILIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY q.id DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
