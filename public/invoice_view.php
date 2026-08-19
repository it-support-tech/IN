<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/InvoiceRepository.php';

use App\Repositories\InvoiceRepository;

$id = (int) ($_GET['id'] ?? 0);
$invoice = (new InvoiceRepository())->findWithDetails($id);

if (!$invoice) {
    http_response_code(404);
    echo 'ບໍ່ພົບໃບເກັບເງິນ';
    exit;
}

$logoDataUri = 'assets/logo (1).png';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title>ໃບເກັບເງິນ <?= htmlspecialchars($invoice['invoice_no']) ?></title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<?php
ob_start();
?>
<a href="index.php" class="btn-solid" style="margin-left:auto;">+ ອອກໃບເກັບເງິນໃໝ່</a>
<button style="width:5rem;" type="button" class="btn-outline" onclick="window.print()">ພິມ</button>
<!-- <a href="invoice_pdf.php?id=<?= $id ?>" class="btn-solid" target="_blank">ດາວໂຫລດ PDF</a> -->
<?php
$navActionsHtml = ob_get_clean();
$activePage = 'invoice_view';
require __DIR__ . '/../templates/nav.php';
?>

<?php
$copyLabel = 'Original';
require __DIR__ . '/../templates/invoice_template.php';
?>
<div class="invoice-page-break"></div>
<?php
$copyLabel = 'Copy';
require __DIR__ . '/../templates/invoice_template.php';
?>

</body>
</html>
