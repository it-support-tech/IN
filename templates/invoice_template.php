<?php

/**
 * Shared invoice HTML partial, rendered both for the on-screen preview
 * (invoice_view.php) and for PDF generation (invoice_pdf.php via dompdf).
 * Expects $invoice (with 'items') from InvoiceRepository::findWithDetails().
 * Expects $logoDataUri (absolute/data URI usable by both browser and dompdf).
 */

/**
 * Shows however many decimal places the staff actually typed: 2 if the value
 * has no meaningful digits past the hundredths place, otherwise 4.
 */
function fmt_money(float $n): string
{
    $decimals = (abs($n - round($n, 2)) < 0.00001) ? 2 : 4;
    return number_format($n, $decimals);
}

$items = $invoice['items'];
?>
<div class="invoice-page">
    <table class="header-table">
        <tr>
            <td class="logo-cell"><img src="<?= htmlspecialchars($logoDataUri) ?>" alt="Logo" class="logo"></td>
            <td class="company-cell">
                <div class="company-name">NTP TRADING PETROLEUM CO., LTD.</div>
                <div>Donglouang Village, Naxay Thong District, Vientiane Capital Laos P.D.R</div>
                <div>Tel. : 030-5888885</div>
                <div>TAX ID : 200510584900</div>
                <div>ntp@gmail.com</div>
            </td>
        </tr>

    </table>
    <table class="meta-table">
        <tr>
            <td class="original-label" colspan="2"><p style="margin-bottom: 10px; margin-right:  3px;">Original</p></td>
        </tr>
        <tr>
            <td class="meta-left">
                <div class="meta-title">ລູກຄ້າ: <?= htmlspecialchars($invoice['customer_code']) ?></div>
                <div><?= htmlspecialchars($invoice['company_name']) ?></div>
                <div><?= htmlspecialchars($invoice['village']) ?> <?= htmlspecialchars($invoice['district']) ?> <?= htmlspecialchars($invoice['province']) ?></div>
              
                <div class="meta-spaced">ເລກທີອາກອນ: <?= htmlspecialchars($invoice['tax_id']) ?></div>
            </td>
            <td class="meta-right">
                <table class="meta-grid">
                    <tr>
                        <td class="meta-label">ໃບເກັບເງິນ</td>
                        <td class="meta-value-700"><?= htmlspecialchars($invoice['invoice_no']) ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label-400">ອາກອນມູນຄ່າເພີ່ມ (ສົ່ນອອກ):</td>
                        <td class="meta-value"><?= htmlspecialchars($invoice['invoice_no']) ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label-400">ໃບສັ່ງເລກທີ:</td>
                        <td class="meta-value"><?= htmlspecialchars($invoice['po_number'] ?? '') ?></td>
                    </tr>
                    <tr class="meta-spaced">
                        <td class="meta-label-400">ການອ້າງອີງ:</td>
                        <td class="meta-value"><?= htmlspecialchars($invoice['invoice_no']) ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label-400">ວັນທີ:</td>
                        <td class="meta-value"><?= htmlspecialchars(date('d/m/Y', strtotime($invoice['invoice_date']))) ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label-400">ວັນທີຄົບກຳນົດ:</td>
                        <td class="meta-value"><?= $invoice['due_date'] ? htmlspecialchars(date('d/m/Y', strtotime($invoice['due_date']))) : '' ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no"></th>
                <th class="col-desc ">#ລາຍການ</th>
                <th class="col-qty">ຈຳນວນ</th>
                <th class="col-price">ລາຄາ</th>
                <th class="col-discount">ສ່ວນຫຼຸດ</th>
                <th class="col-total">ທັງໝົດ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td class="col-no"><?= $i + 1 ?></td>
                    <td class="col-desc"><?= nl2br(htmlspecialchars($item['description'] ?? '')) ?></td>
                    <td class="col-qty"><?= fmt_money((float) $item['quantity']) ?> <?= htmlspecialchars($item['unit'] ?? '') ?></td>
                    <td class="col-price"><?= htmlspecialchars($invoice['currency']) ?> <?= fmt_money((float) $item['unit_price']) ?></td>
                    <td class="col-discount"><?= fmt_money((float) $item['discount']) ?></td>
                    <td class="col-total"><?= htmlspecialchars($invoice['currency']) ?> <?= fmt_money((float) $item['line_total']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td class="bank-info-cell">
                <?php if (!empty($invoice['bank_account_id'])): ?>
                    <div>Bank Name: <?= htmlspecialchars($invoice['bank_name']) ?></div>
                    <div>Name: <?= htmlspecialchars($invoice['bank_account_name']) ?></div>
                    <?php if (!empty($invoice['bank_account_no_lak'])): ?><div>Number: <?= htmlspecialchars($invoice['bank_account_no_lak']) ?> - LAK</div><?php endif; ?>
                    <?php if (!empty($invoice['bank_account_no_usd'])): ?><div>Number: <?= htmlspecialchars($invoice['bank_account_no_usd']) ?> - USD</div><?php endif; ?>
                    <?php if (!empty($invoice['bank_account_no_thb'])): ?><div>Number: <?= htmlspecialchars($invoice['bank_account_no_thb']) ?> - THB</div><?php endif; ?>
                    <?php if (!empty($invoice['bank_swift_code'])): ?><div>Swift Code: <?= htmlspecialchars($invoice['bank_swift_code']) ?></div><?php endif; ?>
                <?php endif; ?>
            </td>
            <td class="totals-cell">
                <table class="totals-table">
                    <tr>
                        <td class="totals-label">ມູນຄ່າລວມບໍ່ມີອາກອນ:</td>
                        <td class="totals-currency"><?= htmlspecialchars($invoice['currency']) ?></td>
                        <td class="totals-value"><?= fmt_money((float) $invoice['subtotal']) ?></td>
                    </tr>
                    <tr>
                        <td class="totals-label">VAT <?= rtrim(rtrim(number_format((float) $invoice['vat_rate'], 2), '0'), '.') ?>%</td>
                        <td class="totals-currency"><?= htmlspecialchars($invoice['currency']) ?></td>
                        <td class="totals-value"><?= fmt_money((float) $invoice['vat_amount']) ?></td>
                    </tr>
                    <tr class="totals-strong">
                        <td class="totals-label">ທັງໝົດ</td>
                        <td class="totals-currency"><?= htmlspecialchars($invoice['currency']) ?></td>
                        <td class="totals-value"><?= fmt_money((float) $invoice['total']) ?></td>
                    </tr>
                    <tr><td></td></tr>
                      <tr><td></td></tr>
                    <tr>
                        <td class="totals-label">ຈຳນວນເງິນທີ່ຍັງຄ້າງ</td>
                        <td class="totals-currency"><?= htmlspecialchars($invoice['currency']) ?></td>
                        <td class="totals-value"><?= fmt_money((float) $invoice['amount_due']) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td><span class="signature-name">NTP TRADING PETROLEUM CO., LTD.</span></td>
            <td><span class="signature-name"><?= htmlspecialchars($invoice['company_name']) ?></span></td>
        </tr>
    </table>
</div>