<?php

namespace App\Repositories;

use App\Database;
use PDO;

final class InvoiceRepository
{
    private const NUMBER_PREFIX = 'IN-126';

    public function suggestNextInvoiceNo(): string
    {
        $stmt = Database::connection()->query(
            "SELECT invoice_no FROM invoices
             WHERE invoice_no LIKE 'IN-126%'
             ORDER BY (substring(invoice_no from 7))::bigint DESC LIMIT 1"
        );
        $last = $stmt->fetchColumn();

        if (!$last) {
            return self::NUMBER_PREFIX . '000001';
        }

        $suffix = substr($last, strlen(self::NUMBER_PREFIX));
        $width = strlen($suffix);
        $next = (int) $suffix + 1;

        return self::NUMBER_PREFIX . str_pad((string) $next, $width, '0', STR_PAD_LEFT);
    }

    /**
     * Creates an invoice + its line items in a single transaction.
     * Line totals and invoice totals (subtotal/VAT/total/amount due) are taken
     * directly from staff input, not recomputed — the business enters these by
     * hand since real invoice amounts don't always match a simple qty*price formula.
     */
    public function create(array $invoiceData, array $items): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $preparedItems = [];
            foreach ($items as $i => $item) {
                $preparedItems[] = [
                    'line_no' => $i + 1,
                    'description' => $item['description'] ?? '',
                    'vehicle_plate' => $item['vehicle_plate'] ?? null,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'discount' => (float) ($item['discount'] ?? 0),
                    'line_total' => (float) ($item['line_total'] ?? 0),
                ];
            }

            $stmt = $pdo->prepare(
                'INSERT INTO invoices
                    (invoice_no, customer_id, bank_account_id, po_number, invoice_date, due_date, currency, subtotal, vat_rate, vat_amount, total, amount_due)
                 VALUES
                    (:invoice_no, :customer_id, :bank_account_id, :po_number, :invoice_date, :due_date, :currency, :subtotal, :vat_rate, :vat_amount, :total, :amount_due)
                 RETURNING id'
            );
            $stmt->execute([
                'invoice_no' => $invoiceData['invoice_no'],
                'customer_id' => (int) $invoiceData['customer_id'],
                'bank_account_id' => !empty($invoiceData['bank_account_id']) ? (int) $invoiceData['bank_account_id'] : null,
                'po_number' => $invoiceData['po_number'] ?? null,
                'invoice_date' => $invoiceData['invoice_date'],
                'due_date' => $invoiceData['due_date'] ?? null,
                'currency' => $invoiceData['currency'] ?? 'USD',
                'subtotal' => (float) ($invoiceData['subtotal'] ?? 0),
                'vat_rate' => (float) ($invoiceData['vat_rate'] ?? 10),
                'vat_amount' => (float) ($invoiceData['vat_amount'] ?? 0),
                'total' => (float) ($invoiceData['total'] ?? 0),
                'amount_due' => (float) ($invoiceData['amount_due'] ?? 0),
            ]);
            $invoiceId = (int) $stmt->fetchColumn();

            $itemStmt = $pdo->prepare(
                'INSERT INTO invoice_items
                    (invoice_id, line_no, description, vehicle_plate, quantity, unit, unit_price, discount, line_total)
                 VALUES
                    (:invoice_id, :line_no, :description, :vehicle_plate, :quantity, :unit, :unit_price, :discount, :line_total)'
            );
            foreach ($preparedItems as $item) {
                $itemStmt->execute(array_merge(['invoice_id' => $invoiceId], $item));
            }

            $pdo->commit();
            return $invoiceId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Updates an invoice's header fields and fully replaces its line items
     * (simplest correct approach since staff can freely add/remove/reorder
     * rows when correcting a mistake, same trust-the-input rule as create()).
     */
    public function update(int $id, array $invoiceData, array $items): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'UPDATE invoices SET
                    invoice_no = :invoice_no,
                    customer_id = :customer_id,
                    bank_account_id = :bank_account_id,
                    po_number = :po_number,
                    invoice_date = :invoice_date,
                    due_date = :due_date,
                    currency = :currency,
                    subtotal = :subtotal,
                    vat_rate = :vat_rate,
                    vat_amount = :vat_amount,
                    total = :total,
                    amount_due = :amount_due
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'invoice_no' => $invoiceData['invoice_no'],
                'customer_id' => (int) $invoiceData['customer_id'],
                'bank_account_id' => !empty($invoiceData['bank_account_id']) ? (int) $invoiceData['bank_account_id'] : null,
                'po_number' => $invoiceData['po_number'] ?? null,
                'invoice_date' => $invoiceData['invoice_date'],
                'due_date' => $invoiceData['due_date'] ?? null,
                'currency' => $invoiceData['currency'] ?? 'USD',
                'subtotal' => (float) ($invoiceData['subtotal'] ?? 0),
                'vat_rate' => (float) ($invoiceData['vat_rate'] ?? 10),
                'vat_amount' => (float) ($invoiceData['vat_amount'] ?? 0),
                'total' => (float) ($invoiceData['total'] ?? 0),
                'amount_due' => (float) ($invoiceData['amount_due'] ?? 0),
            ]);

            $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = :id')->execute(['id' => $id]);

            $itemStmt = $pdo->prepare(
                'INSERT INTO invoice_items
                    (invoice_id, line_no, description, vehicle_plate, quantity, unit, unit_price, discount, line_total)
                 VALUES
                    (:invoice_id, :line_no, :description, :vehicle_plate, :quantity, :unit, :unit_price, :discount, :line_total)'
            );
            foreach ($items as $i => $item) {
                $itemStmt->execute([
                    'invoice_id' => $id,
                    'line_no' => $i + 1,
                    'description' => $item['description'] ?? '',
                    'vehicle_plate' => $item['vehicle_plate'] ?? null,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'discount' => (float) ($item['discount'] ?? 0),
                    'line_total' => (float) ($item['line_total'] ?? 0),
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function findWithDetails(int $id): ?array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare(
            'SELECT i.*, c.code AS customer_code, c.company_name, c.village, c.district, c.province, c.tax_id,
                    b.nickname AS bank_nickname, b.bank_name, b.account_name AS bank_account_name,
                    b.account_no_lak AS bank_account_no_lak, b.account_no_usd AS bank_account_no_usd,
                    b.account_no_thb AS bank_account_no_thb, b.swift_code AS bank_swift_code
             FROM invoices i
             JOIN customers c ON c.id = i.customer_id
             LEFT JOIN bank_accounts b ON b.id = i.bank_account_id
             WHERE i.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return null;
        }

        $itemStmt = $pdo->prepare(
            'SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY line_no ASC'
        );
        $itemStmt->execute(['id' => $id]);
        $invoice['items'] = $itemStmt->fetchAll();

        return $invoice;
    }

    public function listHistory(?string $search = null): array
    {
        $sql = 'SELECT i.id, i.invoice_no, i.invoice_date, i.total, i.currency,
                       c.code AS customer_code, c.company_name
                FROM invoices i
                JOIN customers c ON c.id = i.customer_id';
        $params = [];

        if ($search) {
            $sql .= ' WHERE i.invoice_no ILIKE :search OR c.company_name ILIKE :search OR c.code ILIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY i.id DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
