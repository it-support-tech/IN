<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../repositories/PriceQuoteRepository.php';
require_once __DIR__ . '/../../repositories/CustomerRepository.php';

use App\Repositories\PriceQuoteRepository;
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

$required = ['doc_no', 'doc_date', 'items'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(422);
        echo json_encode(['error' => "Missing required field: {$field}"]);
        exit;
    }
}

if (!empty($input['customer_id'])) {
    $customer = (new CustomerRepository())->findById((int) $input['customer_id']);
    if (!$customer) {
        http_response_code(422);
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }
}

$allowedRateCurrencies = ['USD', 'THB', 'CNY'];
$rateCurrency = in_array($input['rate_currency'] ?? '', $allowedRateCurrencies, true) ? $input['rate_currency'] : 'USD';

$allowedHeaderLangs = ['zh', 'en'];
$headerLang = in_array($input['header_lang'] ?? '', $allowedHeaderLangs, true) ? $input['header_lang'] : 'zh';

$quoteData = [
    'customer_id' => $input['customer_id'] ?? null,
    'doc_no' => $input['doc_no'],
    'doc_date' => $input['doc_date'],
    'currency' => $input['currency'] ?? 'USD',
    'total_usd' => $input['total_usd'] ?? 0,
    'remark' => $input['remark'] ?? null,
    'rate_currency' => $rateCurrency,
    'header_lang' => $headerLang,
];

try {
    $repo = new PriceQuoteRepository();

    if ($method === 'PUT') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Missing document id']);
            exit;
        }
        $repo->update($id, $quoteData, $input['items']);
    } else {
        $id = $repo->create($quoteData, $input['items']);
    }

    echo json_encode(['id' => $id]);
} catch (\Throwable $e) {
    http_response_code(500);
    if (str_contains($e->getMessage(), 'duplicate key') || str_contains($e->getMessage(), 'unique')) {
        echo json_encode(['error' => 'ເລກທີເອກະສານນີ້ຖືກໃຊ້ແລ້ວ, ກະລຸນາປ່ຽນເລກທີ']);
    } else {
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
}
