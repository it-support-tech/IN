<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/CustomerRepository.php';
require_once __DIR__ . '/../repositories/InvoiceRepository.php';
require_once __DIR__ . '/../repositories/BankAccountRepository.php';

use App\Repositories\CustomerRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\BankAccountRepository;

$customers = (new CustomerRepository())->all();
$bankAccounts = (new BankAccountRepository())->all();

$editId = (int) ($_GET['edit'] ?? 0);
$editInvoice = $editId > 0 ? (new InvoiceRepository())->findWithDetails($editId) : null;

$suggestedNo = $editInvoice ? $editInvoice['invoice_no'] : (new InvoiceRepository())->suggestNextInvoiceNo();
$today = $editInvoice ? $editInvoice['invoice_date'] : date('Y-m-d');
$dueDate = $editInvoice ? $editInvoice['due_date'] : date('Y-m-d', strtotime('+30 days'));
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title><?= $editInvoice ? 'ແກ້ໄຂໃບເກັບເງິນ' : 'ອອກໃບເກັບເງິນໃໝ່' ?></title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<?php $activePage = 'index'; require __DIR__ . '/../templates/nav.php'; ?>

<div class="card">
    <h2><?= $editInvoice ? 'ແກ້ໄຂໃບເກັບເງິນ ' . htmlspecialchars($editInvoice['invoice_no']) : 'ອອກໃບເກັບເງິນໃໝ່' ?></h2>
    <form id="invoice-form">
        <div class="form-grid">
            <div class="form-section-title">ຂໍ້ມູນລູກຄ້າ</div>

            <div>
                <label>ລະຫັດລູກຄ້າ</label>
                <select id="customer_id" name="customer_id" required>
                    <option value="">-- ເລືອກລູກຄ້າ --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"
                        data-code="<?= htmlspecialchars($c['code']) ?>"
                        data-company="<?= htmlspecialchars($c['company_name']) ?>"
                        data-village="<?= htmlspecialchars($c['village'] ?? '') ?>"
                        data-district="<?= htmlspecialchars($c['district'] ?? '') ?>"
                        data-province="<?= htmlspecialchars($c['province'] ?? '') ?>"
                        data-tax-id="<?= htmlspecialchars($c['tax_id'] ?? '') ?>"
                        <?= $editInvoice && (int) $editInvoice['customer_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['code']) ?> - <?= htmlspecialchars($c['company_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>

            <div><label>ບໍລິສັດ</label><input type="text" id="company_name" readonly></div>
            <div><label>ບ້ານ/ເມືອງ</label><input type="text" id="village_district" readonly></div>
            <div><label>ແຂວງ</label><input type="text" id="province" readonly></div>
            <div><label>ເລກທີອາກອນ</label><input type="text" id="tax_id" readonly></div>

            <div class="form-section-title">ຂໍ້ມູນໃບເກັບເງິນ</div>

            <div><label>ໃບເກັບເງິນ (ເລກທີ)</label><input type="text" name="invoice_no" id="invoice_no" value="<?= htmlspecialchars($suggestedNo) ?>" required></div>
            <div><label>ໃບສົ່ງເລກທີ </label><input type="text" name="po_number" placeholder="PO: ...."></div>
            <div><label>ວັນທີ</label><input type="date" name="invoice_date" value="<?= $today ?>" required></div>
            <div><label>ວັນທີຄົບກຳນົດ</label><input type="date" name="due_date" value="<?= $dueDate ?>"></div>

            <div>
                <label>ບັນຊີທະນາຄານ</label>
                <select id="bank_account_id" name="bank_account_id">
                    <option value="">-- ບໍ່ສະແດງບັນຊີທະນາຄານ --</option>
                    <?php foreach ($bankAccounts as $b): ?>
                    <option value="<?= (int) $b['id'] ?>"
                        <?= $editInvoice && (int) ($editInvoice['bank_account_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['nickname']) ?> - <?= htmlspecialchars($b['bank_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>
        </div>

        <h3 style="margin-top:24px;">ລາຍການ</h3>
        <table class="line-items" id="items-table">
            <thead>
                <tr>
                    <th>ລາຍການ</th>
                    <th style="width:9%">ຫົວໜ່ວຍ</th>
                    <th style="width:10%">ຈຳນວນ</th>
                    <th style="width:10%">ລາຄາ</th>
                    <th style="width:10%">ສ່ວນຫຼຸດ</th>
                    <th style="width:11%">ທັງໝົດ</th>
                    <th style="width:4%"></th>
                </tr>
            </thead>
            <tbody id="items-body"></tbody>
        </table>
        <button type="button" class="add-row-btn" id="add-row-btn">+ ເພີ່ມລາຍການ</button>

        <div class="totals-box">
            <div class="row"><span>ມູນຄ່າລວມບໍ່ມີອາກອນ</span><input type="number" step="any" id="subtotal-input" value="<?= $editInvoice ? htmlspecialchars((string) $editInvoice['subtotal']) : '0' ?>"></div>
            <div class="row"><span>VAT (10%)</span><input type="number" step="any" id="vat-input" value="<?= $editInvoice ? htmlspecialchars((string) $editInvoice['vat_amount']) : '0' ?>"></div>
            <div class="row grand"><span>ທັງໝົດ</span><input type="number" step="any" id="total-input" value="<?= $editInvoice ? htmlspecialchars((string) $editInvoice['total']) : '0' ?>"></div>
            <div class="row"><span>ຈຳນວນເງິນທີ່ຍັງຄ້າງ</span><input type="number" step="any" id="due-input" value="<?= $editInvoice ? htmlspecialchars((string) $editInvoice['amount_due']) : '0' ?>"></div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn"><?= $editInvoice ? 'ບັນທຶກການແກ້ໄຂ' : 'ບັນທຶກ ແລະ ອອກໃບເກັບເງິນ' ?></button>
        </div>
        <div id="form-error" style="color:#dc2626; margin-top:10px;"></div>
    </form>
</div>

<?php if ($editInvoice): ?>
<script>
window.EDIT_INVOICE = {
    id: <?= (int) $editInvoice['id'] ?>,
    items: <?= json_encode(array_map(fn($it) => [
        'description' => $it['description'],
        'unit' => $it['unit'],
        'quantity' => $it['quantity'],
        'unit_price' => $it['unit_price'],
        'discount' => $it['discount'],
        'line_total' => $it['line_total'],
    ], $editInvoice['items']), JSON_UNESCAPED_UNICODE) ?>,
};
</script>
<?php endif; ?>
<script src="assets/js/invoice.js?v=<?= filemtime(__DIR__ . '/assets/js/invoice.js') ?>"></script>
</body>
</html>
