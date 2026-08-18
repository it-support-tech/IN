CREATE TABLE customers (
    id             SERIAL PRIMARY KEY,
    code           VARCHAR(20) UNIQUE NOT NULL,
    company_name   TEXT NOT NULL,
    village        TEXT,
    district       TEXT,
    province       TEXT,
    tax_id         VARCHAR(30),
    created_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE bank_accounts (
    id              SERIAL PRIMARY KEY,
    nickname        VARCHAR(50) NOT NULL,
    bank_name       TEXT NOT NULL,
    account_name    TEXT NOT NULL,
    account_no_lak  VARCHAR(50),
    account_no_usd  VARCHAR(50),
    account_no_thb  VARCHAR(50),
    swift_code      VARCHAR(20),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE invoices (
    id             SERIAL PRIMARY KEY,
    invoice_no     VARCHAR(20) UNIQUE NOT NULL,
    customer_id    INTEGER NOT NULL REFERENCES customers(id),
    bank_account_id INTEGER REFERENCES bank_accounts(id),
    po_number      VARCHAR(50),
    invoice_date   DATE NOT NULL,
    due_date       DATE,
    currency       VARCHAR(10) NOT NULL DEFAULT 'USD',
    subtotal       NUMERIC(18,8) NOT NULL DEFAULT 0,
    vat_rate       NUMERIC(5,2) NOT NULL DEFAULT 10,
    vat_amount     NUMERIC(18,8) NOT NULL DEFAULT 0,
    total          NUMERIC(18,8) NOT NULL DEFAULT 0,
    amount_due     NUMERIC(18,8) NOT NULL DEFAULT 0,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE invoice_items (
    id             SERIAL PRIMARY KEY,
    invoice_id     INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
    line_no        INTEGER NOT NULL,
    description    TEXT NOT NULL DEFAULT '',
    vehicle_plate  VARCHAR(30),
    quantity       NUMERIC(18,8) NOT NULL DEFAULT 0,
    unit           VARCHAR(20),
    unit_price     NUMERIC(18,8) NOT NULL DEFAULT 0,
    discount       NUMERIC(18,8) NOT NULL DEFAULT 0,
    line_total     NUMERIC(18,8) NOT NULL DEFAULT 0
);

CREATE INDEX idx_invoices_customer_id ON invoices(customer_id);
CREATE INDEX idx_invoice_items_invoice_id ON invoice_items(invoice_id);
