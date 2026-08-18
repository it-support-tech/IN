-- Widens money/quantity columns from NUMERIC(14,4) to NUMERIC(18,8) so staff
-- can enter up to 8 decimal places without the value being truncated.
-- Safe to re-run: altering a column to a type it already has is a no-op.
ALTER TABLE invoices
    ALTER COLUMN subtotal   TYPE NUMERIC(18,8),
    ALTER COLUMN vat_amount TYPE NUMERIC(18,8),
    ALTER COLUMN total      TYPE NUMERIC(18,8),
    ALTER COLUMN amount_due TYPE NUMERIC(18,8);

ALTER TABLE invoice_items
    ALTER COLUMN quantity   TYPE NUMERIC(18,8),
    ALTER COLUMN unit_price TYPE NUMERIC(18,8),
    ALTER COLUMN discount   TYPE NUMERIC(18,8),
    ALTER COLUMN line_total TYPE NUMERIC(18,8);
