<?php
/**
 * Shared sidebar navigation, included by every public page right after
 * <body>. Expects (set by the including page before requiring this file):
 *   $activePage    - one of the keys inside $navGroups below (also used to
 *                    look up the topbar's default title when no
 *                    $navActionsHtml is supplied)
 *   $navActionsHtml - optional pre-rendered HTML (buttons/search) shown in
 *                     the topbar; falls back to the page title below
 */
$navGroups = [
    'ໃບເກັບເງິນ' => [
        'index' => ['index.php', 'ອອກໃບເກັບເງິນໃໝ່', '🧾'],
        'history' => ['history.php', 'ປະຫວັດໃບເກັບເງິນ', '🕘'],
    ],
    'ລາຄາສະເລ່ຍ' => [
        'price_quote' => ['price_quote.php', 'ອອກໃບລາຄາສະເລ່ຍ', '📄'],
        'price_quote_history' => ['price_quote_history.php', 'ປະຫວັດລາຄາສະເລ່ຍ', '🕘'],
    ],
    'ຂໍ້ມູນທົ່ວໄປ' => [
        'customers' => ['customers.php', 'ຂໍ້ມູນລູກຄ້າ', '👤'],
        'bank_accounts' => ['bank_accounts.php', 'ຂໍ້ມູນບັນຊີທະນາຄານ', '🏦'],
    ],
];

$topbarTitle = '';
foreach ($navGroups as $items) {
    if (isset($items[$activePage ?? ''])) {
        $topbarTitle = $items[$activePage][1];
        break;
    }
}
?>
<aside class="app-sidebar no-print">
    <div class="sidebar-brand">
        <img src="assets/logo (1).png" alt="Logo" class="sidebar-logo">
        <div class="sidebar-brand-text">NTP Trading<br>Petroleum</div>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($navGroups as $groupLabel => $items): ?>
        <div class="sidebar-group-label"><?= $groupLabel ?></div>
        <?php foreach ($items as $key => [$href, $label, $icon]): ?>
        <a href="<?= $href ?>" class="sidebar-link<?= ($activePage ?? '') === $key ? ' active' : '' ?>">
            <span class="sidebar-icon"><?= $icon ?></span><span class="sidebar-label"><?= $label ?></span>
        </a>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
</aside>
<div class="app-topbar no-print">
    <?php if (!empty($navActionsHtml)): ?>
        <?= $navActionsHtml ?>
    <?php elseif ($topbarTitle !== ''): ?>
        <span class="app-topbar-title"><?= htmlspecialchars($topbarTitle) ?></span>
    <?php endif; ?>
</div>
