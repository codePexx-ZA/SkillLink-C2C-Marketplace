INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, disputed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'disputed', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM users b
JOIN users s ON s.email = 'nomsa.khumalo@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Nomsa Sparkle Clean'
WHERE b.email = 'lerato.pillay@skilllink.demo'
  AND NOT EXISTS (
      SELECT 1
      FROM orders o
      JOIN users ob ON ob.id = o.buyer_id AND ob.email = 'lerato.pillay@skilllink.demo'
      JOIN users os ON os.id = o.seller_id AND os.email = 'nomsa.khumalo@skilllink.demo'
      WHERE o.status = 'disputed'
  );

INSERT INTO orders (buyer_id, seller_id, listing_id, price_snapshot, thumbnail_url, status, ordered_at, paid_at, disputed_at)
SELECT b.id, s.id, l.id, l.price_snapshot, l.thumbnail_url, 'disputed', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM users b
JOIN users s ON s.email = 'joseph.tshabalala@skilllink.demo'
JOIN listings l ON l.seller_id = s.id AND l.business_name = 'Joseph Custom Wood'
WHERE b.email = 'khanyi.dlamini@skilllink.demo'
  AND NOT EXISTS (
      SELECT 1
      FROM orders o
      JOIN users ob ON ob.id = o.buyer_id AND ob.email = 'khanyi.dlamini@skilllink.demo'
      JOIN users os ON os.id = o.seller_id AND os.email = 'joseph.tshabalala@skilllink.demo'
      WHERE o.status = 'disputed'
  );

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

ALTER TABLE user_account_actions
    MODIFY action ENUM('promote', 'restrict', 'delete', 'ignore') NOT NULL;
