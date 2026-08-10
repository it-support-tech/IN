<?php
/**
 * Shared top navigation, included by every public page.
 * Expects (set by the including page before requiring this file):
 *   $activePage    - one of: index, history, customers, bank_accounts
 *   $navActionsHtml - optional pre-rendered HTML (buttons/search) for the right side
 */
$navItems = [
    'index' => ['index.php', 'ອອກໃບເກັບເງິນໃໝ່'],
    'history' => ['history.php', 'ປະຫວັດໃບເກັບເງິນ'],
    'customers' => ['customers.php', 'ຈັດການລູກຄ້າ'],
    'bank_accounts' => ['bank_accounts.php', 'ຈັດການບັນຊີທະນາຄານ'],
];
?>
<header class="app-header no-print">
    <div class="app-header-inner">
        <div class="brand"><span class="brand-mark">N</span>NTP Invoice</div>
        <nav class="nav-links">
            <?php foreach ($navItems as $key => [$href, $label]): ?>
            <a href="<?= $href ?>" class="nav-link<?= ($activePage ?? '') === $key ? ' active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if (!empty($navActionsHtml)): ?>
        <div class="nav-actions"><?= $navActionsHtml ?></div>
        <?php endif; ?>
    </div>
</header>
