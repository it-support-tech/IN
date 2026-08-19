-- Customer selection on a price statement is optional (staff can issue an
-- average-price statement without picking a specific customer yet).
-- Safe to re-run: dropping a constraint that's already gone is a no-op.
ALTER TABLE price_quotes ALTER COLUMN customer_id DROP NOT NULL;
