<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/PriceQuoteRepository.php';

use App\Repositories\PriceQuoteRepository;

$search = trim($_GET['q'] ?? '');
$quotes = (new PriceQuoteRepository())->listHistory($search ?: null);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title>ປະຫວັດໃບລາຄາສະເລ່ຍ</title>
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
$activePage = 'price_quote_history';
require __DIR__ . '/../templates/nav.php';
?>

<div class="card">
    <h2>ປະຫວັດໃບລາຄາສະເລ່ຍ</h2>
    <table class="history-table">
        <thead>
            <tr>
                <th>ເລກທີເອກະສານ</th>
                <th>ວັນທີ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($quotes)): ?>
            <tr><td colspan="4">ບໍ່ພົບຂໍ້ມູນ</td></tr>
            <?php endif; ?>
            <?php foreach ($quotes as $q): ?>
            <tr>
                <td><?= htmlspecialchars($q['doc_no']) ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($q['doc_date']))) ?></td>
                <td>
                    <a href="price_quote_view.php?id=<?= (int) $q['id'] ?>">ເບິ່ງ</a> |
                    <a href="price_quote.php?edit=<?= (int) $q['id'] ?>">ແກ້ໄຂ</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
