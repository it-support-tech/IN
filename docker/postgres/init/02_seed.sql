INSERT INTO customers (code, company_name, village, district, province, tax_id) VALUES
('CF00018', 'ບໍລິສັດ ກຸ່ມສຳນັກງານທາງລົດໄຟ ຈຳກັດຜູ້ດຽວ (CR-19)', 'ບ້ານ ສະພານທອງໃຕ້', 'ເມືອງ ສີສັດຕະນາກ', 'ນະຄອນຫຼວງ ວຽງຈັນ', '368118215000'),
('CF00013', 'ບໍລິສັດ ພີວີ ລາວ ແລະ ເອັນວີໄອ ຮ່ວມທຶນ ອັງກັດ SEPON MINING', 'ບ້ານ ພະໂພນ', 'ເມືອງ ໄຊບົວທອງ', 'ແຂວງ ສະຫວັນນະເຂດ', '852057219-0-00')
ON CONFLICT (code) DO NOTHING;

INSERT INTO bank_accounts (nickname, bank_name, account_name, account_no_lak, account_no_usd, account_no_thb, swift_code) VALUES
('BCEL One', 'BANQUE POUR LE COMMERCE EXTERIEUR LAO PUBLIC', 'NTP Trading Petroleum Company Limited', '0301100001337263001', '0301101013372603001', '0301102013372603001', 'COEBLALA');

-- Historical invoices to seed the "next number" suggestion baseline (IN-126080056 was the last real invoice used).
INSERT INTO invoices (invoice_no, customer_id, po_number, invoice_date, due_date, currency, subtotal, vat_rate, vat_amount, total, amount_due)
SELECT 'IN-126080042', id, 'PO: 3260-70298', '2026-08-05', '2026-09-19', 'USD', 46181.82, 10, 4618.18, 50800.00, 50800.00
FROM customers WHERE code = 'CF00013'
ON CONFLICT (invoice_no) DO NOTHING;

INSERT INTO invoice_items (invoice_id, line_no, description, quantity, unit, unit_price, discount, line_total)
SELECT id, 1, E'DBO-T-0001: Diesel\nSO-0126080039\nບກ 8314\nສາງ: Savannkhet - Fuel, ຄັງທີ່ຮັບ: F-DB-7 : Fuel', 40000, 'Litter', 1.15, 0, 46181.82
FROM invoices WHERE invoice_no = 'IN-126080042'
ON CONFLICT DO NOTHING;

INSERT INTO invoices (invoice_no, customer_id, po_number, invoice_date, due_date, currency, subtotal, vat_rate, vat_amount, total, amount_due)
SELECT 'IN-126080056', id, 'PO: 0002-26', '2026-08-07', '2026-10-06', 'USD', 47585.45, 10, 4758.55, 52344.00, 52344.00
FROM customers WHERE code = 'CF00018'
ON CONFLICT (invoice_no) DO NOTHING;

INSERT INTO invoice_items (invoice_id, line_no, description, quantity, unit, unit_price, discount, line_total)
SELECT id, 1, E'DBO-T-0001: Diesel\nSO-0126080048\nບກ 5831\nສາງ: Savannkhet - Fuel, ຄັງທີ່ຮັບ: F-DB-7 : Fuel', 40000, 'Litter', 1.19, 0, 47585.45
FROM invoices WHERE invoice_no = 'IN-126080056'
ON CONFLICT DO NOTHING;
