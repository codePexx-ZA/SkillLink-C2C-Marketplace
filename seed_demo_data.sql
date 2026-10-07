INSERT IGNORE INTO categories (slug, name) VALUES
('tree-felling', 'Tree Felling'),
('plumbing', 'Plumbing'),
('electrical', 'Electrical'),
('gardening', 'Gardening'),
('painting', 'Painting'),
('cleaning', 'Cleaning'),
('carpentry', 'Carpentry'),
('other', 'Other');

INSERT INTO users (email, password_hash, role) VALUES
('thabo.mokoena@skilllink.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('lerato.pillay@skilllink.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('khanyi.dlamini@skilllink.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('nomsa.khumalo@skilllink.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('joseph.tshabalala@skilllink.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO profiles (user_id, display_name, username, primary_category_slug, profile_photo)
SELECT id, 'Thabo Mokoena', 'thabo_tree', 'tree-felling', NULL
FROM users WHERE email = 'thabo.mokoena@skilllink.demo';

INSERT INTO profiles (user_id, display_name, username, primary_category_slug, profile_photo)
SELECT id, 'Lerato Pillay', 'lerato_plumb', 'plumbing', NULL
FROM users WHERE email = 'lerato.pillay@skilllink.demo';

INSERT INTO profiles (user_id, display_name, username, primary_category_slug, profile_photo)
SELECT id, 'Khanyi Dlamini', 'khanyi_elec', 'electrical', NULL
FROM users WHERE email = 'khanyi.dlamini@skilllink.demo';

INSERT INTO profiles (user_id, display_name, username, primary_category_slug, profile_photo)
SELECT id, 'Nomsa Khumalo', 'nomsa_clean', 'cleaning', NULL
FROM users WHERE email = 'nomsa.khumalo@skilllink.demo';

INSERT INTO profiles (user_id, display_name, username, primary_category_slug, profile_photo)
SELECT id, 'Joseph Tshabalala', 'joseph_carp', 'carpentry', NULL
FROM users WHERE email = 'joseph.tshabalala@skilllink.demo';

INSERT INTO billing_info (user_id, billing_email, card_brand, billing_address)
SELECT id, 'thabo.billing@skilllink.demo', 'Visa', '12 Oak Street, Pretoria, 0002'
FROM users WHERE email = 'thabo.mokoena@skilllink.demo';

INSERT INTO billing_info (user_id, billing_email, card_brand, billing_address)
SELECT id, 'lerato.billing@skilllink.demo', 'Mastercard', '88 Main Road, Cape Town, 8001'
FROM users WHERE email = 'lerato.pillay@skilllink.demo';

INSERT INTO billing_info (user_id, billing_email, card_brand, billing_address)
SELECT id, 'khanyi.billing@skilllink.demo', 'Visa', '4 Mandela Drive, Durban, 4001'
FROM users WHERE email = 'khanyi.dlamini@skilllink.demo';

INSERT INTO billing_info (user_id, billing_email, card_brand, billing_address)
SELECT id, 'nomsa.billing@skilllink.demo', 'Visa', '21 Church Lane, Johannesburg, 2001'
FROM users WHERE email = 'nomsa.khumalo@skilllink.demo';

INSERT INTO billing_info (user_id, billing_email, card_brand, billing_address)
SELECT id, 'joseph.billing@skilllink.demo', 'Mastercard', '9 Workshop Ave, Bloemfontein, 9301'
FROM users WHERE email = 'joseph.tshabalala@skilllink.demo';

INSERT INTO listings (seller_id, category_id, business_name, price_snapshot, thumbnail_url, status, description, location, price_amount, price_unit)
SELECT u.id, c.id, 'Thabo Tree Services', 1200.00, 'https://picsum.photos/seed/thabo-tree/400/300', 'active',
       'Emergency tree felling, stump removal, and garden clearance. Fully insured crew.',
       'Pretoria East', 1200.00, 'per job'
FROM users u
JOIN categories c ON c.slug = 'tree-felling'
WHERE u.email = 'thabo.mokoena@skilllink.demo';

INSERT INTO listings (seller_id, category_id, business_name, price_snapshot, thumbnail_url, status, description, location, price_amount, price_unit)
SELECT u.id, c.id, 'Lerato Plumbing Co', 650.00, 'https://picsum.photos/seed/lerato-plumb/400/300', 'active',
       'Burst pipes, geyser installs, drain unblocking. Same-day callouts in Cape Town.',
       'Cape Town CBD', 650.00, 'per hour'
FROM users u
JOIN categories c ON c.slug = 'plumbing'
WHERE u.email = 'lerato.pillay@skilllink.demo';

INSERT INTO listings (seller_id, category_id, business_name, price_snapshot, thumbnail_url, status, description, location, price_amount, price_unit)
SELECT u.id, c.id, 'Khanyi Electrical', 950.00, 'https://picsum.photos/seed/khanyi-elec/400/300', 'active',
       'DB board upgrades, wiring, and COC certificates for homes and small businesses.',
       'Durban North', 950.00, 'per job'
FROM users u
JOIN categories c ON c.slug = 'electrical'
WHERE u.email = 'khanyi.dlamini@skilllink.demo';

INSERT INTO listings (seller_id, category_id, business_name, price_snapshot, thumbnail_url, status, description, location, price_amount, price_unit)
SELECT u.id, c.id, 'Nomsa Sparkle Clean', 450.00, 'https://picsum.photos/seed/nomsa-clean/400/300', 'pending_review',
       'Deep cleaning for flats and offices. Eco-friendly products available.',
       'Sandton, Johannesburg', 450.00, 'per visit'
FROM users u
JOIN categories c ON c.slug = 'cleaning'
WHERE u.email = 'nomsa.khumalo@skilllink.demo';

INSERT INTO listings (seller_id, category_id, business_name, price_snapshot, thumbnail_url, status, description, location, price_amount, price_unit)
SELECT u.id, c.id, 'Joseph Custom Wood', 1800.00, 'https://picsum.photos/seed/joseph-carp/400/300', 'active',
       'Built-in cupboards, decking, and furniture repairs. Free on-site quote.',
       'Bloemfontein Central', 1800.00, 'per project'
FROM users u
JOIN categories c ON c.slug = 'carpentry'
WHERE u.email = 'joseph.tshabalala@skilllink.demo';

INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, confirmed_at, completed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'placed', NOW(), NULL, NULL, NULL
FROM users b
JOIN users s ON s.email = 'lerato.pillay@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Lerato Plumbing Co'
WHERE b.email = 'thabo.mokoena@skilllink.demo';

INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, confirmed_at, completed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'paid', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL
FROM users b
JOIN users s ON s.email = 'khanyi.dlamini@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Khanyi Electrical'
WHERE b.email = 'lerato.pillay@skilllink.demo';

INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, confirmed_at, completed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'confirmed', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), NULL
FROM users b
JOIN users s ON s.email = 'nomsa.khumalo@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Nomsa Sparkle Clean'
WHERE b.email = 'khanyi.dlamini@skilllink.demo';

INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, confirmed_at, completed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'completed', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)
FROM users b
JOIN users s ON s.email = 'joseph.tshabalala@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Joseph Custom Wood'
WHERE b.email = 'nomsa.khumalo@skilllink.demo';

INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, disputed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'disputed', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)
FROM users b
JOIN users s ON s.email = 'thabo.mokoena@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Thabo Tree Services'
WHERE b.email = 'joseph.tshabalala@skilllink.demo';

INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, disputed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'disputed', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users b
JOIN users s ON s.email = 'nomsa.khumalo@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Nomsa Sparkle Clean'
WHERE b.email = 'lerato.pillay@skilllink.demo';

INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, disputed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'disputed', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM users b
JOIN users s ON s.email = 'joseph.tshabalala@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Joseph Custom Wood'
WHERE b.email = 'khanyi.dlamini@skilllink.demo';

INSERT INTO reviews (order_id, listing_id, reviewer_id, stars, review_text)
SELECT o.id, o.listing_id, o.buyer_id, 5, 'Excellent craftsmanship. Cupboards fitted perfectly and on time.'
FROM orders o
JOIN users b ON b.id = o.buyer_id AND b.email = 'nomsa.khumalo@skilllink.demo'
WHERE o.status = 'completed'
LIMIT 1;

INSERT INTO contact_messages (user_id, name, email, message, status)
SELECT id, 'Thabo Mokoena', 'thabo.mokoena@skilllink.demo', 'How do I update my listing photos?', 'new'
FROM users WHERE email = 'thabo.mokoena@skilllink.demo';

INSERT INTO contact_messages (user_id, name, email, message, status)
SELECT id, 'Lerato Pillay', 'lerato.pillay@skilllink.demo', 'Payment still showing as pending on my last order.', 'in_progress'
FROM users WHERE email = 'lerato.pillay@skilllink.demo';

INSERT INTO contact_messages (user_id, name, email, message, status)
VALUES (NULL, 'Guest Visitor', 'guest@skilllink.demo', 'Do you cover rural areas outside Durban?', 'new');

INSERT INTO moderation_flags (listing_id, raised_by_id, flag_type, action_taken)
SELECT l.id, b.id, 'other', 'flagged'
FROM listings l
JOIN users s ON s.id = l.seller_id AND s.email = 'nomsa.khumalo@skilllink.demo'
JOIN users b ON b.email = 'khanyi.dlamini@skilllink.demo'
WHERE l.business_name = 'Nomsa Sparkle Clean';

INSERT INTO disputes (order_id, buyer_id, seller_id, status, assigned_admin_id, description)
SELECT o.id, o.buyer_id, o.seller_id, 'open', NULL,
       'Buyer reports incomplete tree removal; seller says scope was agreed as stump only.'
FROM orders o
JOIN users b ON b.id = o.buyer_id AND b.email = 'joseph.tshabalala@skilllink.demo'
JOIN users s ON s.id = o.seller_id AND s.email = 'thabo.mokoena@skilllink.demo'
WHERE o.status = 'disputed'
LIMIT 1;

INSERT INTO disputes (order_id, buyer_id, seller_id, status, assigned_admin_id, description)
SELECT o.id, o.buyer_id, o.seller_id, 'open', NULL,
       'Buyer claims office clean was below standard; seller says extra rooms were not booked.'
FROM orders o
JOIN users b ON b.id = o.buyer_id AND b.email = 'lerato.pillay@skilllink.demo'
JOIN users s ON s.id = o.seller_id AND s.email = 'nomsa.khumalo@skilllink.demo'
WHERE o.status = 'disputed'
  AND NOT EXISTS (
      SELECT 1 FROM disputes d WHERE d.order_id = o.id
  )
LIMIT 1;

INSERT INTO disputes (order_id, buyer_id, seller_id, status, assigned_admin_id, description)
SELECT o.id, o.buyer_id, o.seller_id, 'open', NULL,
       'Buyer alleges faulty cupboard fitting; seller says damage was pre-existing on delivery.'
FROM orders o
JOIN users b ON b.id = o.buyer_id AND b.email = 'khanyi.dlamini@skilllink.demo'
JOIN users s ON s.id = o.seller_id AND s.email = 'joseph.tshabalala@skilllink.demo'
WHERE o.status = 'disputed'
  AND NOT EXISTS (
      SELECT 1 FROM disputes d WHERE d.order_id = o.id
  )
LIMIT 1;

INSERT INTO verifications (user_id, status, reviewed_by_id, notes)
SELECT id, 'pending', NULL, 'ID uploaded. Awaiting trade reference check.'
FROM users WHERE email = 'joseph.tshabalala@skilllink.demo';

INSERT INTO verifications (user_id, status, reviewed_by_id, notes, reviewed_at)
SELECT id, 'approved', NULL, 'All documents verified.', NOW()
FROM users WHERE email = 'thabo.mokoena@skilllink.demo';

INSERT INTO verifications (user_id, status, reviewed_by_id, notes, reviewed_at)
SELECT id, 'approved', NULL, 'Bank details confirmed.', NOW()
FROM users WHERE email = 'lerato.pillay@skilllink.demo';

INSERT INTO verifications (user_id, status, reviewed_by_id, notes)
SELECT id, 'pending', NULL, 'Missing clear profile photo.'
FROM users WHERE email = 'nomsa.khumalo@skilllink.demo';

INSERT INTO verifications (user_id, status, reviewed_by_id, notes, reviewed_at)
SELECT id, 'rejected', NULL, 'Certification document expired.', NOW()
FROM users WHERE email = 'khanyi.dlamini@skilllink.demo';

INSERT INTO payout_batches (batch_code, total_amount, status, processed_at)
VALUES ('BATCH-2026-06-04A', 4850.00, 'processed', NOW());

INSERT INTO payouts (seller_id, batch_id, total_amount, status, paid_at)
SELECT u.id, pb.id, 1200.00, 'paid', NOW()
FROM users u
JOIN payout_batches pb ON pb.batch_code = 'BATCH-2026-06-04A'
WHERE u.email = 'thabo.mokoena@skilllink.demo';

INSERT INTO payouts (seller_id, batch_id, total_amount, status, paid_at)
SELECT u.id, pb.id, 650.00, 'paid', NOW()
FROM users u
JOIN payout_batches pb ON pb.batch_code = 'BATCH-2026-06-04A'
WHERE u.email = 'lerato.pillay@skilllink.demo';

INSERT INTO payouts (seller_id, batch_id, total_amount, status, failure_reason)
SELECT u.id, pb.id, 950.00, 'failed', 'Bank account name mismatch.'
FROM users u
JOIN payout_batches pb ON pb.batch_code = 'BATCH-2026-06-04A'
WHERE u.email = 'khanyi.dlamini@skilllink.demo';

INSERT INTO payouts (seller_id, batch_id, total_amount, status)
SELECT u.id, pb.id, 1800.00, 'pending'
FROM users u
JOIN payout_batches pb ON pb.batch_code = 'BATCH-2026-06-04A'
WHERE u.email = 'joseph.tshabalala@skilllink.demo';
