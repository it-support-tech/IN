<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/CustomerRepository.php';

use App\Repositories\CustomerRepository;

$repo = new CustomerRepository();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'code' => trim($_POST['code'] ?? ''),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'village' => trim($_POST['village'] ?? ''),
        'district' => trim($_POST['district'] ?? ''),
        'province' => trim($_POST['province'] ?? ''),
        'tax_id' => trim($_POST['tax_id'] ?? ''),
    ];

    if ($data['code'] === '' || $data['company_name'] === '') {
        $errors[] = 'ກະລຸນາປ້ອນລະຫັດລູກຄ້າ ແລະ ຊື່ບໍລິສັດ';
    } else {
        try {
            $editId = (int) ($_POST['id'] ?? 0);
            if ($editId > 0) {
                $repo->update($editId, $data);
            } else {
                $repo->create($data);
            }
            header('Location: customers.php');
            exit;
        } catch (\Throwable $e) {
            $errors[] = str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')
                ? 'ລະຫັດລູກຄ້ານີ້ຖືກໃຊ້ແລ້ວ'
                : 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage();
        }
    }
}

$editingCustomer = null;
if (!empty($_GET['edit'])) {
    $editingCustomer = $repo->findById((int) $_GET['edit']);
}

$customers = $repo->all();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title>ຂໍ້ມູນລູກຄ້າ</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<?php $activePage = 'customers'; require __DIR__ . '/../templates/nav.php'; ?>

<div class="card">
    <h2><?= $editingCustomer ? 'ແກ້ໄຂລູກຄ້າ' : 'ເພີ່ມລູກຄ້າໃໝ່' ?></h2>
    <?php foreach ($errors as $err): ?>
        <p style="color:#dc2626;"><?= htmlspecialchars($err) ?></p>
    <?php endforeach; ?>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editingCustomer['id'] ?? '' ?>">
        <div class="form-grid">
            <div><label>ລະຫັດລູກຄ້າ</label><input type="text" name="code" value="<?= htmlspecialchars($editingCustomer['code'] ?? '') ?>" required></div>
            <div><label>ຊື່ບໍລິສັດ</label><input type="text" name="company_name" value="<?= htmlspecialchars($editingCustomer['company_name'] ?? '') ?>" required></div>
            <div><label>ບ້ານ</label><input type="text" name="village" value="<?= htmlspecialchars($editingCustomer['village'] ?? '') ?>"></div>
            <div><label>ເມືອງ</label><input type="text" name="district" value="<?= htmlspecialchars($editingCustomer['district'] ?? '') ?>"></div>
            <div><label>ແຂວງ</label><input type="text" name="province" value="<?= htmlspecialchars($editingCustomer['province'] ?? '') ?>"></div>
            <div><label>ເລກທີອາກອນ</label><input type="text" name="tax_id" value="<?= htmlspecialchars($editingCustomer['tax_id'] ?? '') ?>"></div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" class="btn"><?= $editingCustomer ? 'ບັນທຶກການແກ້ໄຂ' : 'ເພີ່ມລູກຄ້າ' ?></button>
            <?php if ($editingCustomer): ?><a href="customers.php" class="btn secondary" style="text-decoration:none; display:inline-block;">ຍົກເລີກ</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>ລາຍຊື່ລູກຄ້າ</h2>
    <table class="customer-table">
        <thead>
            <tr>
                <th>ລະຫັດ</th><th>ຊື່ບໍລິສັດ</th><th>ບ້ານ/ເມືອງ</th><th>ແຂວງ</th><th>ເລກທີອາກອນ</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['code']) ?></td>
                <td><?= htmlspecialchars($c['company_name']) ?></td>
                <td><?= htmlspecialchars($c['village'] ?? '') ?> <?= htmlspecialchars($c['district'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['province'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['tax_id'] ?? '') ?></td>
                <td><a href="customers.php?edit=<?= (int) $c['id'] ?>">ແກ້ໄຂ</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
