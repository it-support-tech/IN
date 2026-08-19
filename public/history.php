<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/InvoiceRepository.php';

use App\Repositories\InvoiceRepository;

$search = trim($_GET['q'] ?? '');
$invoices = (new InvoiceRepository())->listHistory($search ?: null);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title>ປະຫວັດໃບເກັບເງິນ</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<?php
ob_start();
?>
<form method="get" style="display:flex;">
    <input style="color:black;" type="text" name="q" placeholder="ຄົ້ນຫາ ເລກທີ/ລູກຄ້າ" value="<?= htmlspecialchars($search) ?>">
</form>
<?php
$navActionsHtml = ob_get_clean();
$activePage = 'history';
require __DIR__ . '/../templates/nav.php';
?>

<div class="card">
    <h2>ປະຫວັດໃບເກັບເງິນ</h2>
    <table class="history-table">
        <thead>
            <tr>
                <th>ເລກທີໃບເກັບເງິນ</th>
                <th>ລູກຄ້າ</th>
                <th>ວັນທີ</th>
                <th>ຍອດລວມ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
            <tr><td colspan="5">ບໍ່ພົບຂໍ້ມູນ</td></tr>
            <?php endif; ?>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td><?= htmlspecialchars($inv['invoice_no']) ?></td>
                <td><?= htmlspecialchars($inv['customer_code']) ?> - <?= htmlspecialchars($inv['company_name']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($inv['invoice_date']))) ?></td>
                <td><?= htmlspecialchars($inv['currency']) ?> <?= number_format((float) $inv['total'], 2) ?></td>
                <td>
                    <a href="invoice_view.php?id=<?= (int) $inv['id'] ?>">ເບິ່ງ</a> |
                    <a href="index.php?edit=<?= (int) $inv['id'] ?>">ແກ້ໄຂ</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
