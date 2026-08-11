<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../repositories/InvoiceRepository.php';

use App\Repositories\InvoiceRepository;

header('Content-Type: application/json; charset=utf-8');

echo json_encode(['invoice_no' => (new InvoiceRepository())->suggestNextInvoiceNo()]);
