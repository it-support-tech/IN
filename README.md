# ລະບົບອອກໃບເກັບເງິນ NTP Trading Petroleum

PHP (pure PDO, no framework) + PostgreSQL invoice system, containerized with Docker Compose.
Generates A4 invoices matching the NTP Trading Petroleum layout, with real PDF export (dompdf),
customer master data, and invoice history.

## Run it

```bash
docker compose up -d --build
```

Then open http://localhost:8090/index.php

(Ports are set in `.env` — `APP_PORT=8090` and `DB_HOST_PORT=5434` — chosen to avoid clashing
with other local projects. Change them there if 8090/5434 are also taken on your machine.)

First boot auto-creates the schema and seeds two sample customers (`CF00018`, `CF00013`) plus
two historical invoices, from `docker/postgres/init/*.sql`.

## Replace the logo

`public/assets/logo-placeholder.svg` is a placeholder. Replace that file with the real NTP
logo (keep the same filename, or update the `<img src="...">` reference in
`templates/invoice_template.php` and the data-URI load in `public/invoice_pdf.php`).
The CSS (`.logo { width: 80px; }` in `public/assets/css/style.css`) controls the printed size —
adjust that one rule if the real logo's proportions differ.

## Lao text in the PDF

The `app` image installs `fonts-noto-core` (includes Noto Sans Lao), and
`public/invoice_pdf.php` registers it with dompdf as the default font — Lao script renders
correctly in the downloaded PDF, not just in the browser preview. If you ever change fonts,
keep both a `normal` and a `bold` weight registered (`Dompdf\FontMetrics::registerFont`) and
only use `font-weight: 400` or `700` in `style.css` for anything inside `.invoice-page` —
intermediate weights (e.g. `600`) that were never registered can cause corrupted/tofu glyphs
for characters not covered by dompdf's weight-matching fallback.

## Structure

- `docker/` — Dockerfile, entrypoint, DB init SQL
- `config/Database.php` — PDO singleton
- `repositories/` — CustomerRepository, InvoiceRepository (all SQL, server-side total recompute)
- `templates/invoice_template.php` — single shared HTML partial used both on-screen and for PDF
- `public/` — index.php (new invoice), invoice_view.php, invoice_pdf.php, history.php, customers.php, api/
