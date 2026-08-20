<?php

/**
 * Shared price-quote (average price statement) HTML partial, rendered both
 * for the on-screen preview (price_quote_view.php) and for PDF generation
 * (price_quote_pdf.php via dompdf). Mirrors invoice_template.php's header
 * style per explicit request, with its own meta block and items table.
 * Expects $quote (with 'items') from PriceQuoteRepository::findWithDetails().
 * Expects $logoDataUri (absolute/data URI usable by both browser and dompdf).
 */

function fmt_qty(float $n, int $decimals = 2): string
{
    return number_format($n, $decimals);
}

/**
 * Staff can choose whether the items-table subtitles show Chinese or
 * English alongside the Lao headers (ລາວ-ຈີນ / ລາວ-ອັງກິດ).
 */
function header_subtitle(string $lang, string $zh, string $en): string
{
    return $lang === 'en' ? $en : $zh;
}

/**
 * Bilingual Chinese header subtitles are pre-rendered PNGs (not live text)
 * because dompdf has a table-rendering bug where only the first cell per
 * row using a non-default font renders any glyph — every other cell using
 * that same custom font in the row comes out blank, regardless of whether
 * the font is shared or registered under a unique family per cell. Data URIs
 * (not relative src paths) so this also works from dompdf's loadHtml(),
 * which has no base path to resolve relative URLs against.
 */
// function cjk_label(string $key): string
// {
//     $path = __DIR__ . '/../public/assets/images/' . $key . '.png';
//     $dataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
//     return '<img src="' . htmlspecialchars($dataUri) . '" class="cjk-img" alt="">';
// }

$items = $quote['items'];
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
    <div class="doc-title">ໃບຄິດໄລ່ລາຄາສະເລ່ຍ</div>
    <table class="meta-table">
        <tr>
            <td class="meta-spacer"></td>
            <td class="meta-cell">
                <table class="meta-grid">
                    <tr>
                        <td class="meta-label">ເລກທີເອກະສານ:</td>
                        <td class="meta-value-700"><?= htmlspecialchars($quote['doc_no']) ?></td>
                    </tr>
                    <tr>
                        <td class="meta-label-400">ວັນທີ:</td>
                        <td class="meta-value"><?= htmlspecialchars(date('d/m/Y', strtotime($quote['doc_date']))) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table quote-items-table">
        <thead>
            <?php $hl = $quote['header_lang'] ?? 'zh'; ?>
            <tr>
                <th>ຈຳນວນລິດ<br><?= header_subtitle($hl, '油量', 'Quantity') ?></th>
                <th>ລາຄາໂຄງສ້າງລັດທະບານ<br><?= header_subtitle($hl, '政府指导价', 'Structure Price') ?></th>
                <th>ສ່ວນຫຼຸດ<br><?= header_subtitle($hl, '优惠', 'Discount') ?></th>
                <th>ລາຄາຫຼັງຫຼຸດ<br><?= header_subtitle($hl, '后优惠价格', 'Price After Discount') ?></th>
                <th>ອັດຕາແລກປ່ຽນ<br><?= header_subtitle($hl, '汇率', 'Exchange Rate') ?></th>
                <th>ລາຄາ<br><?= header_subtitle($hl, '美金', 'Price') ?></th>
                <th>ຈຳນວນເງິນທັງໝົດ<br><?= header_subtitle($hl, '总价格', 'Total Amount') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= fmt_qty((float) $item['quantity_liters'], 0) ?></td>
                    <td><?= fmt_qty((float) $item['structure_price'], 0) ?></td>
                    <td><?= fmt_qty((float) $item['discount'], 0) ?></td>
                    <td><?= fmt_qty((float) $item['price_after_discount'], 0) ?></td>
                    <td class="quote-total-cell"><span><?= fmt_qty((float) $item['exchange_rate'], 0) ?></span></td>
                    <td class="quote-total-cell"><span><?= htmlspecialchars($quote['rate_currency']) ?></span><span><?= fmt_qty((float) $item['usd_price'], 4) ?></span></td>
                    <td class="quote-total-cell"><span>LAK</span><span><?= fmt_qty((float) $item['total_amount'], 0) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="quote-total-row">
                <td colspan="6" class="quote-total-label"></td>
                <td class="quote-total-cell"><span><?= htmlspecialchars($quote['rate_currency']) ?></span><span><?= fmt_qty((float) $quote['total_usd'], 2) ?></span></td>
            </tr>
        </tfoot>
    </table>

    <?php if (!empty($quote['remark'])): ?>
        <div class="quote-remark"><strong>ໝາຍເຫດ:</strong> <?= nl2br(htmlspecialchars($quote['remark'])) ?></div>
    <?php endif; ?>

    <table class="signature-table quote-signature-table">
        <tr>
            <td></td>
            <td><span class="signature-name">ຜູ້ສະຫຼຸບ</span></td>
        </tr>
    </table>
</div>