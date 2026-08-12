<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/BankAccountRepository.php';

use App\Repositories\BankAccountRepository;

$repo = new BankAccountRepository();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete') {
        $repo->delete((int) $_POST['id']);
        header('Location: bank_accounts.php');
        exit;
    }

    $data = [
        'nickname' => trim($_POST['nickname'] ?? ''),
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'account_name' => trim($_POST['account_name'] ?? ''),
        'account_no_lak' => trim($_POST['account_no_lak'] ?? ''),
        'account_no_usd' => trim($_POST['account_no_usd'] ?? ''),
        'account_no_thb' => trim($_POST['account_no_thb'] ?? ''),
        'swift_code' => trim($_POST['swift_code'] ?? ''),
    ];

    if ($data['nickname'] === '' || $data['bank_name'] === '' || $data['account_name'] === '') {
        $errors[] = 'ກະລຸນາປ້ອນຊື່ຫຍໍ້, ຊື່ທະນາຄານ ແລະ ຊື່ບັນຊີ';
    } else {
        try {
            $editId = (int) ($_POST['id'] ?? 0);
            if ($editId > 0) {
                $repo->update($editId, $data);
            } else {
                $repo->create($data);
            }
            header('Location: bank_accounts.php');
            exit;
        } catch (\Throwable $e) {
            $errors[] = 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage();
        }
    }
}

$editingAccount = null;
if (!empty($_GET['edit'])) {
    $editingAccount = $repo->findById((int) $_GET['edit']);
}

$accounts = $repo->all();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="UTF-8">
<title>ຂໍ້ມູນບັນຊີທະນາຄານ</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>

<?php $activePage = 'bank_accounts'; require __DIR__ . '/../templates/nav.php'; ?>

<div class="card">
    <h2><?= $editingAccount ? 'ແກ້ໄຂບັນຊີທະນາຄານ' : 'ເພີ່ມບັນຊີທະນາຄານໃໝ່' ?></h2>
    <?php foreach ($errors as $err): ?>
        <p style="color:#dc2626;"><?= htmlspecialchars($err) ?></p>
    <?php endforeach; ?>
    <form method="post">
        <input type="hidden" name="id" value="<?= $editingAccount['id'] ?? '' ?>">
        <div class="form-grid">
            <div><label>ຊື່ຫຍໍ້</label><input type="text" name="nickname" value="<?= htmlspecialchars($editingAccount['nickname'] ?? '') ?>" required></div>
            <div><label>Bank Name</label><input type="text" name="bank_name" value="<?= htmlspecialchars($editingAccount['bank_name'] ?? '') ?>" required></div>
            <div><label>Name (ຊື່ບັນຊີ)</label><input type="text" name="account_name" value="<?= htmlspecialchars($editingAccount['account_name'] ?? '') ?>" required></div>
            <div><label>Swift Code</label><input type="text" name="swift_code" value="<?= htmlspecialchars($editingAccount['swift_code'] ?? '') ?>"></div>
            <div><label>Number - LAK</label><input type="text" name="account_no_lak" value="<?= htmlspecialchars($editingAccount['account_no_lak'] ?? '') ?>"></div>
            <div><label>Number - USD</label><input type="text" name="account_no_usd" value="<?= htmlspecialchars($editingAccount['account_no_usd'] ?? '') ?>"></div>
            <div><label>Number - THB</label><input type="text" name="account_no_thb" value="<?= htmlspecialchars($editingAccount['account_no_thb'] ?? '') ?>"></div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" class="btn"><?= $editingAccount ? 'ບັນທຶກການແກ້ໄຂ' : 'ເພີ່ມບັນຊີ' ?></button>
            <?php if ($editingAccount): ?><a href="bank_accounts.php" class="btn secondary" style="text-decoration:none; display:inline-block;">ຍົກເລີກ</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>ລາຍຊື່ບັນຊີທະນາຄານ</h2>
    <table class="customer-table">
        <thead>
            <tr>
                <th>ຊື່ຫຍໍ້</th><th>Bank Name</th><th>Name</th><th>LAK</th><th>USD</th><th>THB</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($accounts)): ?>
            <tr><td colspan="7">ຍັງບໍ່ມີບັນຊີທະນາຄານ</td></tr>
            <?php endif; ?>
            <?php foreach ($accounts as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['nickname']) ?></td>
                <td><?= htmlspecialchars($a['bank_name']) ?></td>
                <td><?= htmlspecialchars($a['account_name']) ?></td>
                <td><?= htmlspecialchars($a['account_no_lak'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['account_no_usd'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['account_no_thb'] ?? '') ?></td>
                <td>
                    <a href="bank_accounts.php?edit=<?= (int) $a['id'] ?>">ແກ້ໄຂ</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('ລຶບບັນຊີນີ້ບໍ?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;padding:0;margin-left:8px;">ລຶບ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
