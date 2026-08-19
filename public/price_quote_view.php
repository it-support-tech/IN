<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/PriceQuoteRepository.php';

use App\Repositories\PriceQuoteRepository;

$id = (int) ($_GET['id'] ?? 0);
$quote = (new PriceQuoteRepository())->findWithDetails($id);

if (!$quote) {
    http_response_code(404);
    echo 'ບໍ່ພົບໃບລາຄາສະເລ່ຍ';
    exit;
}

$logoDataUri = 'assets/logo (1).png';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title>ໃບລາຄາສະເລ່ຍ <?= htmlspecialchars($quote['doc_no']) ?></title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<?php
ob_start();
?>
<a href="price_quote.php" class="btn-solid" style="margin-left:auto;">+ ອອກໃບລາຄາສະເລ່ຍໃໝ່</a>
<button  style="width:5rem;" type="button" class="btn-outline" onclick="window.print()">ພິມ</button>
<!-- <a href="price_quote_pdf.php?id=<?= $id ?>" class="btn-solid" target="_blank">ດາວໂຫລດ PDF</a> -->
<?php
$navActionsHtml = ob_get_clean();
$activePage = 'price_quote_view';
require __DIR__ . '/../templates/nav.php';
?>

<?php require __DIR__ . '/../templates/price_quote_template.php'; ?>

</body>
</html>
