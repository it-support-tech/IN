-- Adds the vehicle registration/license plate field to line items.
-- Safe to re-run: only applies if the column is missing (e.g. on a database
-- that was already initialized before this column existed in 01_schema.sql).
ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS vehicle_plate VARCHAR(30);
