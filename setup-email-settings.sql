-- Email notification settings for Jambo Masai Tours
-- Import via phpMyAdmin after running this file

INSERT INTO site_settings (setting_key, setting_value) VALUES
  ('smtp_host',           ''),
  ('smtp_port',           '587'),
  ('smtp_user',           ''),
  ('smtp_pass',           ''),
  ('smtp_from_email',     'info@jambomasaitours.com'),
  ('smtp_from_name',      'Jambo Masai Tours'),
  ('admin_notify_email',  'info@jambomasaitours.com'),
  ('notify_on_booking',   '1'),
  ('notify_on_contact',   '1'),
  ('notify_customer',     '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
