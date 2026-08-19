-- Line totals (total_amount) come out in Kip since price_after_discount is
-- LAK-denominated (exchange_rate * usd_price); this holds the separately
-- calculated grand total converted to USD.
-- Safe to re-run: adding a column that already exists is a no-op.
ALTER TABLE price_quotes ADD COLUMN IF NOT EXISTS total_usd NUMERIC(18,8) NOT NULL DEFAULT 0;
