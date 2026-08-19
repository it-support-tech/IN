<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/PriceQuoteRepository.php';

use App\Repositories\PriceQuoteRepository;

$editId = (int) ($_GET['edit'] ?? 0);
$editQuote = $editId > 0 ? (new PriceQuoteRepository())->findWithDetails($editId) : null;

$suggestedNo = $editQuote ? $editQuote['doc_no'] : (new PriceQuoteRepository())->suggestNextDocNo();
$today = $editQuote ? $editQuote['doc_date'] : date('Y-m-d');
$docRate = $editQuote && !empty($editQuote['items']) ? (float) $editQuote['items'][0]['exchange_rate'] : null;
$docRateCurrency = $editQuote['rate_currency'] ?? 'USD';
$headerLang = $editQuote['header_lang'] ?? 'zh';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title><?= $editQuote ? 'ແກ້ໄຂໃບລາຄາສະເລ່ຍ' : 'ອອກໃບລາຄາສະເລ່ຍໃໝ່' ?></title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<?php $activePage = 'price_quote'; require __DIR__ . '/../templates/nav.php'; ?>

<div class="card">
    <h2><?= $editQuote ? 'ແກ້ໄຂໃບລາຄາສະເລ່ຍ ' . htmlspecialchars($editQuote['doc_no']) : 'ອອກໃບລາຄາສະເລ່ຍໃໝ່' ?></h2>
    <form id="quote-form">
        <div class="form-grid">
            <div class="form-section-title">ຂໍ້ມູນໃບລາຄາສະເລ່ຍ</div>

            <div><label>ເລກທີເອກະສານ</label><input type="text" name="doc_no" id="doc_no" value="<?= htmlspecialchars($suggestedNo) ?>" required></div>
            <div><label>ວັນທີ</label><input type="date" name="doc_date" value="<?= $today ?>" required></div>
            <div><label>ໝາຍເຫດ</label><input type="text" name="remark" id="remark" value="<?= $editQuote ? htmlspecialchars((string) ($editQuote['remark'] ?? '')) : '' ?>"></div>
            <div style="margin-left:-8px;">
                <label>ອັດຕາແລກປ່ຽນ (Rate)</label>
                <div class="field-inline">
                    <select id="rate-currency">
                        <option value="USD"<?= $docRateCurrency === 'USD' ? ' selected' : '' ?>>USD</option>
                        <option value="THB"<?= $docRateCurrency === 'THB' ? ' selected' : '' ?>>THB</option>
                        <option value="CNY"<?= $docRateCurrency === 'CNY' ? ' selected' : '' ?>>CNY</option>
                    </select>
                    <input type="number" step="any" id="doc-rate" placeholder="0" value="<?= $docRate !== null ? htmlspecialchars((string) $docRate) : '' ?>">
                </div>
            </div>
            <div>
                <label>ວິທີຄິດໄລ່ລາຄາ</label>
                <select id="calc-mode">
                    <option value="1">ຫາລາຄາໂດລາ</option>
                    <option value="2">ຫາລາຄາສ່ວນຫຼຸດ</option>
                </select>
            </div>
            <div>
                <label>ພາສາ</label>
                <select id="header-lang">
                    <option value="zh"<?= $headerLang === 'zh' ? ' selected' : '' ?>>ລາວ-ຈີນ</option>
                    <option value="en"<?= $headerLang === 'en' ? ' selected' : '' ?>>ລາວ-ອັງກິດ</option>
                </select>
            </div>
        </div>

        <h3 style="margin-top:24px;">ລາຍການລາຄາ</h3>
        <table class="line-items" id="items-table">
            <thead>
                <tr>
                    <th style="width:14%">ຈຳນວນລິດ</th>
                    <th style="width:14%">ລາຄາໂຄງສ້າງ</th>
                    <th style="width:13%">ສ່ວນຫຼຸດ</th>
                    <th style="width:14%">ລາຄາຫຼັງ</th>
                    <th style="width:15%">ລາຄາ</th>
                    <th style="width:22%">ຈຳນວນເງິນທັງໝົດ</th>
                    <th style="width:4%"></th>
                </tr>
            </thead>
            <tbody id="items-body"></tbody>
        </table>
        <button type="button" class="add-row-btn" id="add-row-btn">+ ເພີ່ມລາຍການ</button>

        <div class="totals-box">
            <div class="row grand">
                <span>ຈຳນວນລວມທັງໝົດ (<span id="grand-total-currency-label"><?= htmlspecialchars($docRateCurrency) ?></span>)</span>
                <input type="number" step="any" id="total-usd-input" placeholder="0"
                    <?= $editQuote ? 'value="' . htmlspecialchars((string) (float) $editQuote['total_usd']) . '"' : '' ?>>
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn"><?= $editQuote ? 'ບັນທຶກການແກ້ໄຂ' : 'ບັນທຶກ ແລະ ອອກໃບລາຄາສະເລ່ຍ' ?></button>
        </div>
        <div id="form-error" style="color:#dc2626; margin-top:10px;"></div>
    </form>
</div>

<?php if ($editQuote): ?>
<script>
window.EDIT_QUOTE = {
    id: <?= (int) $editQuote['id'] ?>,
    items: <?= json_encode(array_map(fn($it) => [
        'quantity_liters' => $it['quantity_liters'],
        'structure_price' => $it['structure_price'],
        'discount' => $it['discount'],
        'price_after_discount' => $it['price_after_discount'],
        'exchange_rate' => $it['exchange_rate'],
        'usd_price' => $it['usd_price'],
        'total_amount' => $it['total_amount'],
    ], $editQuote['items']), JSON_UNESCAPED_UNICODE) ?>,
};
</script>
<?php endif; ?>
<script src="assets/js/price_quote.js?v=<?= filemtime(__DIR__ . '/assets/js/price_quote.js') ?>"></script>
</body>
</html>
