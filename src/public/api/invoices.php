<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../repositories/InvoiceRepository.php';
require_once __DIR__ . '/../../repositories/CustomerRepository.php';

use App\Repositories\InvoiceRepository;
use App\Repositories\CustomerRepository;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST' && $method !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$required = ['customer_id', 'invoice_no', 'invoice_date', 'items'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(422);
        echo json_encode(['error' => "Missing required field: {$field}"]);
        exit;
    }
}

$customer = (new CustomerRepository())->findById((int) $input['customer_id']);
if (!$customer) {
    http_response_code(422);
    echo json_encode(['error' => 'Customer not found']);
    exit;
}

if (!preg_match('/^IN-126\d+$/', $input['invoice_no'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Invoice number must start with IN-126']);
    exit;
}

$invoiceData = [
    'customer_id' => $input['customer_id'],
    'bank_account_id' => $input['bank_account_id'] ?? null,
    'invoice_no' => $input['invoice_no'],
    'po_number' => $input['po_number'] ?? null,
    'invoice_date' => $input['invoice_date'],
    'due_date' => $input['due_date'] ?? null,
    'currency' => $input['currency'] ?? 'USD',
    'subtotal' => $input['subtotal'] ?? 0,
    'vat_rate' => $input['vat_rate'] ?? 10,
    'vat_amount' => $input['vat_amount'] ?? 0,
    'total' => $input['total'] ?? 0,
    'amount_due' => $input['amount_due'] ?? 0,
];

try {
    $repo = new InvoiceRepository();

    if ($method === 'PUT') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Missing invoice id']);
            exit;
        }
        $repo->update($id, $invoiceData, $input['items']);
    } else {
        $id = $repo->create($invoiceData, $input['items']);
    }

    echo json_encode(['id' => $id]);
} catch (\Throwable $e) {
    http_response_code(500);
    if (str_contains($e->getMessage(), 'duplicate key') || str_contains($e->getMessage(), 'unique')) {
        echo json_encode(['error' => 'ເລກທີໃບເກັບເງິນນີ້ຖືກໃຊ້ແລ້ວ, ກະລຸນາປ່ຽນເລກທີ']);
    } else {
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
}
