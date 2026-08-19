-- Exchange rate moved from a per-line field to a single document-level rate;
-- this records which foreign currency (USD/THB/CNY) that rate is quoted in.
-- Safe to re-run: adding a column that already exists is a no-op.
ALTER TABLE price_quotes ADD COLUMN IF NOT EXISTS rate_currency VARCHAR(3) NOT NULL DEFAULT 'USD';
