-- Lets staff choose whether the printed items-table subtitles show Chinese
-- or English translations alongside the Lao headers.
-- Safe to re-run: adding a column that already exists is a no-op.
ALTER TABLE price_quotes ADD COLUMN IF NOT EXISTS header_lang VARCHAR(2) NOT NULL DEFAULT 'zh';
