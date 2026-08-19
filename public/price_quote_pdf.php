<?php
// dompdf's bundled Cpdf.php calls ctype_xdigit(null) while parsing some font
// glyph tables, which PHP 8.1+ reports as deprecated; left on, those notices
// get written into the binary PDF stream and corrupt the output.
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/PriceQuoteRepository.php';

use App\Repositories\PriceQuoteRepository;
use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int) ($_GET['id'] ?? 0);
$quote = (new PriceQuoteRepository())->findWithDetails($id);

if (!$quote) {
    http_response_code(404);
    echo 'ບໍ່ພົບໃບລາຄາສະເລ່ຍ';
    exit;
}

$logoFile = __DIR__ . '/assets/logo (1).png';
if (is_file($logoFile)) {
    $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile));
} else {
    $logoFile = __DIR__ . '/assets/logo-placeholder.svg';
    $logoDataUri = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoFile));
}

$css = file_get_contents(__DIR__ . '/assets/css/style.css');

$pdfOverrideCss = '
    body { margin: 0; padding: 0; background: #fff; }
    .invoice-page { box-shadow: none; margin: 0; width: auto; min-height: auto; }
    @page { margin: 0; }
';

ob_start();
require __DIR__ . '/../templates/price_quote_template.php';
$bodyHtml = ob_get_clean();

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $css . $pdfOverrideCss . '</style></head><body>' . $bodyHtml . '</body></html>';

$fontDir = sys_get_temp_dir() . '/dompdf-fonts';
if (!is_dir($fontDir)) {
    mkdir($fontDir, 0777, true);
}

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Noto Sans Lao');
$options->set('fontDir', $fontDir);
$options->set('fontCache', $fontDir);
$options->setChroot(array_merge($options->getChroot(), ['/usr/share/fonts']));

$dompdf = new Dompdf($options);
$dompdf->getFontMetrics()->registerFont(
    ['family' => 'Noto Sans Lao', 'style' => 'normal', 'weight' => 'normal'],
    '/usr/share/fonts/truetype/noto/NotoSansLao-Regular.ttf'
);
$dompdf->getFontMetrics()->registerFont(
    ['family' => 'Noto Sans Lao', 'style' => 'normal', 'weight' => 'bold'],
    '/usr/share/fonts/truetype/noto/NotoSansLao-Bold.ttf'
);
$dompdf->getFontMetrics()->registerFont(
    ['family' => 'Noto Sans', 'style' => 'normal', 'weight' => 'normal'],
    '/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf'
);
$dompdf->getFontMetrics()->registerFont(
    ['family' => 'Noto Sans', 'style' => 'normal', 'weight' => 'bold'],
    '/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf'
);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = $quote['doc_no'] . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);
