-- Seed data tambahan: orders + order_items + tickets untuk demo customer/orders/refund.
-- Jalankan SETELAH schema.sql + migration 001 supaya tabel + kolom siap.

INSERT INTO orders (order_uid, tenant_id, user_id, event_id, order_code, customer_name, customer_email, customer_phone, total_amount_cents, currency, status, payment_provider, payment_reference, created_at)
VALUES
  ('ord-budi-001-uid', 1, 2, 1, 'ORD-BUDI-001', 'Budi',         'admin@acousticnights.com', '081200000001', 200000, 'idr', 'paid',    'midtrans', 'midtrans-ref-001', NOW() - INTERVAL 4 DAY),
  ('ord-budi-002-uid', 1, 2, 2, 'ORD-BUDI-002', 'Budi',         'admin@acousticnights.com', '081200000001', 150000, 'idr', 'pending', 'xendit',   NULL,                NOW() - INTERVAL 2 DAY),
  ('ord-tika-001-uid', 1, NULL, 1, 'ORD-TIKA-001', 'Tika Nirwana','tika@example.com',        '081299999999', 350000, 'idr', 'paid',    'doku',     'doku-ref-tika',     NOW() - INTERVAL 3 DAY);

INSERT INTO order_items (order_id, event_id, seat_label, category_id, price_cents)
SELECT o.id, 1, 'A-1', 1, 200000 FROM orders o WHERE o.order_code = 'ORD-BUDI-001';
INSERT INTO order_items (order_id, event_id, seat_label, category_id, price_cents)
SELECT o.id, 2, NULL, NULL, 150000 FROM orders o WHERE o.order_code = 'ORD-BUDI-002';
INSERT INTO order_items (order_id, event_id, seat_label, category_id, price_cents)
SELECT o.id, 1, 'A-2', 1, 200000 FROM orders o WHERE o.order_code = 'ORD-TIKA-001';
INSERT INTO order_items (order_id, event_id, seat_label, category_id, price_cents)
SELECT o.id, 1, 'A-3', 1, 150000 FROM orders o WHERE o.order_code = 'ORD-TIKA-001';

INSERT INTO tickets (ticket_uid, order_id, event_id, seat_label, ticket_token, status, issued_at)
SELECT CONCAT('tk-', SUBSTRING(MD5(RAND()), 1, 12)), o.id, 1, 'A-1', UUID(), 'valid', NOW() FROM orders o WHERE o.order_code='ORD-BUDI-001';
INSERT INTO tickets (ticket_uid, order_id, event_id, seat_label, ticket_token, status, issued_at)
SELECT CONCAT('tk-', SUBSTRING(MD5(RAND()), 1, 12)), o.id, 1, 'A-2', UUID(), 'valid', NOW() FROM orders o WHERE o.order_code='ORD-TIKA-001';
INSERT INTO tickets (ticket_uid, order_id, event_id, seat_label, ticket_token, status, issued_at)
SELECT CONCAT('tk-', SUBSTRING(MD5(RAND()), 1, 12)), o.id, 1, 'A-3', UUID(), 'valid', NOW() FROM orders o WHERE o.order_code='ORD-TIKA-001';

-- Mark sold seats untuk konser akustik
UPDATE seats SET status='sold' WHERE event_id=1 AND seat_label IN ('A-1','A-2','A-3');
