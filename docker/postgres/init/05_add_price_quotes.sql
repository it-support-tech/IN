-- New module: average-price statement issued to a customer (separate from
-- invoices.doc_no format is YYYYMM + '00' + a running number, e.g. 20260800001).
CREATE TABLE IF NOT EXISTS price_quotes (
    id             SERIAL PRIMARY KEY,
    doc_no         VARCHAR(20) UNIQUE NOT NULL,
    customer_id    INTEGER REFERENCES customers(id),
    doc_date       DATE NOT NULL,
    currency       VARCHAR(10) NOT NULL DEFAULT 'USD',
    total_usd      NUMERIC(18,8) NOT NULL DEFAULT 0,
    remark         TEXT,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS price_quote_items (
    id                    SERIAL PRIMARY KEY,
    quote_id              INTEGER NOT NULL REFERENCES price_quotes(id) ON DELETE CASCADE,
    line_no               INTEGER NOT NULL,
    quantity_liters       NUMERIC(18,8) NOT NULL DEFAULT 0,
    structure_price       NUMERIC(18,8) NOT NULL DEFAULT 0,
    discount              NUMERIC(18,8) NOT NULL DEFAULT 0,
    price_after_discount  NUMERIC(18,8) NOT NULL DEFAULT 0,
    exchange_rate         NUMERIC(18,8) NOT NULL DEFAULT 0,
    usd_price             NUMERIC(18,8) NOT NULL DEFAULT 0,
    total_amount          NUMERIC(18,8) NOT NULL DEFAULT 0,
    remark                TEXT
);

CREATE INDEX IF NOT EXISTS idx_price_quotes_customer_id ON price_quotes(customer_id);
CREATE INDEX IF NOT EXISTS idx_price_quote_items_quote_id ON price_quote_items(quote_id);
