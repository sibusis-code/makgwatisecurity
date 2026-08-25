-- Makgwati Security CMS — MySQL schema + seed data
-- Import this once via phpMyAdmin (or `mysql -u ... -p dbname < schema.sql`)
-- after creating the database in cPanel. Seed data mirrors the content
-- that is currently hardcoded across index.html / services.html /
-- training.html / contact.html / chatbot.js, so nothing is lost when the
-- site switches from static HTML to DB-driven pages.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Admin users (multi-admin login, replaces the single-file auth.json)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Site-wide key/value settings (phone numbers, credentials, hero copy)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_settings (
  `key`   VARCHAR(100) PRIMARY KEY,
  `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO site_settings (`key`, `value`) VALUES
  ('whatsapp_number',      '27790260098'),
  ('phone_head_office',    '015 001 2295'),
  ('head_office_contact',  'Ally'),
  ('psira_number',         '4464345'),
  ('training_number',      '4333959'),
  ('saps_number',          '4001370'),
  ('pftc_number',          'T2311004'),
  ('professionals_trained','500+'),
  ('training_pass_rate',   '95%'),
  ('branch_count',         '6'),
  ('hero_badge',           'PSIRA Certified Elite Security'),
  ('hero_title',           'Protect What <span class="highlight">Matters Most</span> With Elite Security'),
  ('hero_subtitle',        'Makgwati Security delivers world-class protection services across South Africa — from VIP close protection and armed response to professional PSIRA-certified training.'),
  ('footer_description',   'Elite security solutions for individuals, businesses, and organisations across South Africa. PSIRA registered and fully certified.'),
  ('copyright_name',       'Makgwati Security')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ---------------------------------------------------------------------
-- Services (drives home-page cards + full services.html catalogue)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  icon_class VARCHAR(60) NOT NULL,
  summary TEXT,                 -- short blurb used on the home page card
  description TEXT,             -- longer blurb used on services.html
  features JSON,                -- bullet list shown on services.html, e.g. ["Feature one","Feature two"]
  whatsapp_text VARCHAR(255),   -- pre-filled WhatsApp enquiry text
  link_href VARCHAR(255),       -- home-card link target (services.html / training.html / vipprotection.php)
  link_text VARCHAR(60),        -- home-card link label ("Learn More" / "View Gallery" / "View Courses")
  show_on_home TINYINT(1) NOT NULL DEFAULT 1,
  show_on_services TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO services (title, icon_class, summary, description, features, whatsapp_text, link_href, link_text, show_on_home, show_on_services, sort_order) VALUES
('VIP & Close Protection', 'fas fa-user-shield',
 'Discreet, professional close protection officers for executives, public figures, and high-risk individuals. Multi-layer protocols and threat assessment.',
 'Discreet, professional close protection for executives, public figures, dignitaries, and high-risk individuals. Our CPOs are highly trained with real-world field experience.',
 JSON_ARRAY('Certified Close Protection Officers','Multi-layer security protocols','Advance threat assessment','Secure transportation coordination'),
 'I am interested in VIP / Close Protection services from Makgwati Security', 'vipprotection.php', 'View Gallery', 1, 1, 1),

('Building & Site Security', 'fas fa-building',
 'Professional uniformed security officers for commercial buildings, industrial sites, retail centres, and residential estates.',
 'Professional uniformed security officers deployed at commercial buildings, industrial sites, retail centres, warehouses, and residential estates.',
 JSON_ARRAY('Uniformed PSIRA-registered guards','Access point management','Shift-based 24/7 deployment','Incident reporting and logs'),
 'I am interested in Building Security services from Makgwati Security', 'services.php', 'Learn More', 1, 1, 2),

('Armed Response', 'fas fa-shield-alt',
 'Rapid armed response teams on standby 24/7. Swift intervention for residential, commercial, and industrial clients across our coverage areas.',
 'Rapid armed response units on standby 24/7. Our teams are trained to respond swiftly and decisively to any security threat at your premises.',
 JSON_ARRAY('Rapid response deployment','Armed and SAPS-compliant officers','Residential and commercial coverage','Panic button integration'),
 'I am interested in Armed Response services from Makgwati Security', 'services.php', 'Learn More', 1, 1, 3),

('Event Security Management', 'fas fa-calendar-check',
 'Comprehensive event security management including crowd control, access management, plainclothes officers, and emergency response teams.',
 'Full event security management for corporate functions, concerts, sporting events, conferences, and private celebrations. We handle crowds so you can focus on your event.',
 JSON_ARRAY('Crowd control specialists','Plainclothes and uniformed officers','Access and ticket management','Emergency response coordination'),
 'I am interested in Event Security Management from Makgwati Security', 'services.php', 'Learn More', 1, 1, 4),

('Riot Intervention', 'fas fa-people-arrows',
 'Specialised riot intervention and crowd management teams, trained to de-escalate volatile situations and protect people and property during civil unrest.',
 'Specialised riot intervention and crowd management teams, trained to de-escalate volatile situations and protect people and property during civil unrest.',
 JSON_ARRAY('Trained riot response units','Non-lethal intervention techniques','De-escalation specialists','Property and asset protection'),
 'I am interested in Riot Intervention services from Makgwati Security', 'services.php', 'Learn More', 0, 1, 5),

('CCTV Installation & Management', 'fas fa-video',
 'Full CCTV installation, configuration, and monitoring. Remote viewing capability and access control integration for complete site awareness.',
 'Professional CCTV design, supply, installation, and ongoing monitoring. We provide full surveillance solutions for homes, businesses, and large commercial properties.',
 JSON_ARRAY('HD camera installation','Remote viewing setup (mobile & PC)','NVR/DVR configuration','24/7 monitoring service'),
 'I am interested in CCTV Installation from Makgwati Security', 'services.php', 'Learn More', 1, 1, 6),

('Access Control Systems', 'fas fa-door-closed',
 'State-of-the-art access control systems for offices, residential complexes, and industrial facilities.',
 'State-of-the-art access control systems for offices, residential complexes, and industrial facilities. Control who enters and exits your premises at all times.',
 JSON_ARRAY('Biometric and card reader systems','Boom gate and turnstile installation','Intercom and video entry systems','Integration with CCTV'),
 'I am interested in Access Control Systems from Makgwati Security', 'services.php', 'Learn More', 0, 1, 7),

('Fire Alarm Installation', 'fas fa-fire-extinguisher',
 'Professional fire detection and alarm system installation, compliant with South African fire safety regulations.',
 'Professional fire detection and alarm system installation for commercial, industrial, and residential properties. Compliant with South African fire safety regulations.',
 JSON_ARRAY('Smoke and heat detector installation','Central panel and siren systems','SANS compliance','Maintenance and servicing'),
 'I am interested in Fire Alarm Installation from Makgwati Security', 'services.php', 'Learn More', 0, 1, 8),

('Car Guarding', 'fas fa-car',
 'Professional car park and vehicle guarding services for shopping centres, businesses, events, and residential complexes.',
 'Professional car park and vehicle guarding services for shopping centres, businesses, events, and residential complexes. Prevent theft and ensure customer safety.',
 JSON_ARRAY('Trained and uniformed car guards','Vehicle security monitoring','Parking area patrol','Incident reporting'),
 'I am interested in Car Guarding services from Makgwati Security', 'services.php', 'Learn More', 0, 1, 9),

('Cash In Transit (CIT)', 'fas fa-money-bill-wave',
 'Secure cash-in-transit operations executed by specially trained CIT officers.',
 'Secure cash-in-transit operations executed by specially trained CIT officers. Protecting your cash movements from collection point to destination.',
 JSON_ARRAY('PSIRA-certified CIT officers','Armed escort procedures','Route risk assessment','Strict chain-of-custody protocols'),
 'I am interested in CIT services from Makgwati Security', 'services.php', 'Learn More', 0, 1, 10),

('PSIRA Training', 'fas fa-graduation-cap',
 'Accredited training programs including Security Grades A & B, EDC, CIT, Reaction Unit, and full firearm competency courses. PSIRA certified.',
 NULL, NULL, NULL, 'training.php', 'View Courses', 1, 0, 11);

-- ---------------------------------------------------------------------
-- Training courses (drives training.html cards, dropdown, footer list,
-- and the chatbot's pricing answer — single source of truth for price)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS training_courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(100) NOT NULL,   -- 'Security Grade Courses' | 'Firearm Competency Training'
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO training_courses (category, name, description, price, sort_order) VALUES
('Security Grade Courses', 'Grade A', 'Entry-level PSIRA security officer training. Covers fundamental security principles, access control, patrol procedures, and legal powers of a security officer.', 1300.00, 1),
('Security Grade Courses', 'Grade B', 'Supervisory security training for experienced officers. Covers team leadership, advanced procedures, report writing, and supervisory responsibilities.', 1200.00, 2),
('Security Grade Courses', 'EDC — Elementary Development Certificate', 'Foundation security education program. Ideal for those new to the security industry. Covers basic principles, conduct, and professional development.', 2400.00, 3),
('Security Grade Courses', 'CIT — Cash In Transit', 'Specialised training for cash-in-transit security operations. Covers armed escort, cash handling protocols, threat assessment, and emergency procedures.', 1400.00, 4),
('Security Grade Courses', 'Reaction Unit', 'Emergency response and armed reaction training. Covers rapid response tactics, coordination, communication under pressure, and scene assessment.', 1400.00, 5),
('Firearm Competency Training', 'Handgun — Private License', 'SAPS-aligned training for individuals seeking a private handgun license. Covers safe handling, storage, legal compliance, and practical competency assessment.', 2100.00, 6),
('Firearm Competency Training', 'Handgun — Business License', 'Comprehensive training for business firearm license applicants. Covers legal obligations, workplace firearm policies, safe storage, and competency requirements.', 2600.00, 7),
('Firearm Competency Training', 'Shotgun — Business License', 'Specialised shotgun training for business license applicants. Covers firearm mechanics, safe handling, legal requirements, and SAPS competency assessment.', 2600.00, 8),
('Firearm Competency Training', 'Rifle — Business License', 'Advanced rifle training aligned with the Firearms Control Act. Covers operation, maintenance, legal compliance, and business licensing requirements.', 2600.00, 9);

-- ---------------------------------------------------------------------
-- Testimonials
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_name VARCHAR(120) NOT NULL,
  author_role VARCHAR(150),
  rating TINYINT NOT NULL DEFAULT 5,
  quote_text TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO testimonials (author_name, author_role, rating, quote_text, sort_order) VALUES
('Sipho M.', 'Corporate Event Manager, Polokwane', 5, 'Makgwati Security handled our corporate gala with exceptional professionalism. Their team was discreet, well-coordinated, and responded to every concern immediately. Highly recommended.', 1),
('Tebogo K.', 'PSIRA Grade A Graduate', 5, 'I enrolled for my Grade A and firearm training with Makgwati. The instructors are top-tier and the facilities are excellent. I passed first time and secured employment immediately after.', 2),
('N. Dlamin', 'Executive Client, Limpopo', 5, 'The VIP protection team from Makgwati was outstanding during our high-risk business trip. Professional, alert, and always three steps ahead. I feel genuinely safe when they are involved.', 3);

-- ---------------------------------------------------------------------
-- Branches (drives contact.html cards, footer contact block, chatbot)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  contact_person VARCHAR(120),
  phone VARCHAR(30),
  whatsapp VARCHAR(30),
  badge VARCHAR(20),               -- e.g. 'HQ', 'NEW'
  is_head_office TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO branches (name, contact_person, phone, whatsapp, badge, is_head_office, sort_order) VALUES
('Head Office', 'Ally', '015 001 2295', '27790260098', 'HQ', 1, 1),
('Jane Furse', 'Beauty', NULL, '27822271165', NULL, 0, 2),
('Driekop', 'Kgaugelo', NULL, '27769537244', NULL, 0, 3),
('Monsterlus', 'Kamo', NULL, '27820724878', NULL, 0, 4),
('Makeketela', 'Charity', NULL, '27822847799', NULL, 0, 5),
('Mogwase', 'David', '079 716 5314', '27706247673', 'NEW', 0, 6);

-- ---------------------------------------------------------------------
-- Chatbot FAQ knowledge base
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS faqs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  keywords VARCHAR(500) NOT NULL,   -- comma-separated, matched with `input.includes(keyword)` like today
  answer_html TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO faqs (keywords, answer_html, sort_order) VALUES
('service,offer,provide,what do,what you',
 '🛡️ <strong>Our Services Include:</strong><br>• VIP / Close Protection<br>• Building Security (armed &amp; unarmed)<br>• Armed Response<br>• Event Security Management<br>• Riot Intervention<br>• CCTV Installation<br>• Access Control Systems<br>• Fire Alarm Installation<br>• Car Guarding<br>• Cash In Transit (CIT)<br><br>Visit our <a href="services.html" style="color:var(--gold);">Services page</a> to see all options.', 1),
('price,cost,how much,fee,charge,rate,affordable',
 '💰 <strong>Training Prices:</strong><br>• Grade A — <strong>R1,300</strong><br>• Grade B — <strong>R1,200</strong><br>• EDC — <strong>R2,400</strong><br>• CIT — <strong>R1,400</strong><br>• Reaction Unit — <strong>R1,400</strong><br>• Handgun Private — <strong>R2,100</strong><br>• Handgun / Shotgun / Rifle Business — <strong>R2,600</strong><br><br>For security service quotes, please share your requirements — we''ll tailor a package for you.', 2),
('psira,registered,certified,accredited,licence,legitimate,legal,registration number',
 '✅ <strong>We Are Fully Certified:</strong><br>• PSIRA Reg: <strong>4464345</strong><br>• Training Number: <strong>4333959</strong><br>• SAPS Number: <strong>4001370</strong><br>• PFTC Number: <strong>T2311004</strong><br><br>All officers are PSIRA registered and fully vetted.', 3),
('location,where,branch,office,area,province,limpopo,north west',
 '📍 <strong>Our Branch Locations:</strong><br>• <strong>Head Office</strong> — Ally: 079 026 0098<br>• <strong>Jane Furse</strong> — Beauty: 082 227 1165<br>• <strong>Driekop</strong> — Kgaugelo: 076 953 7244<br>• <strong>Monsterlus</strong> — Kamo: 082 072 4878<br>• <strong>Makeketela</strong> — Charity: 082 284 7799<br>• <strong>Mogwase</strong> — David: 070 624 7673 / 079 716 5314<br><br><a href="contact.html" style="color:var(--gold);">View all branch details →</a>', 4),
('firearm,gun,handgun,rifle,shotgun,weapon,competency,pistol',
 '🔫 <strong>Firearm Competency Training:</strong><br>• Handgun — Private License: <strong>R2,100</strong><br>• Handgun — Business License: <strong>R2,600</strong><br>• Shotgun — Business License: <strong>R2,600</strong><br>• Rifle — Business License: <strong>R2,600</strong><br><br>SAPS-aligned, conducted at our accredited range (PFTC: T2311004). Would you like to enroll?', 5),
('vip,protection,bodyguard,close,escort,executive,celebrity',
 '⭐ <strong>VIP / Close Protection:</strong><br>We provide discreet, professional close protection for executives, politicians, celebrities, and high-net-worth individuals.<br><br><strong>Services include:</strong><br>• Personal close protection officers<br>• Secure transportation<br>• Advance security sweeps<br>• Multi-team coordination<br>• 24/7 coverage available<br><br>View our <a href="vipprotection.php" style="color:var(--gold);">VIP gallery and assignment videos →</a>', 6),
('enroll,register,join,sign up,how to apply,start training,apply',
 '🎓 <strong>How to Enroll:</strong><br>1. Choose your course from our <a href="training.html" style="color:var(--gold);">Training page</a><br>2. Fill in the enrollment form, OR<br>3. Click <strong>Enroll Now</strong> on any course card<br>4. We''ll confirm your enrollment via WhatsApp<br><br>Requirements: Valid ID, PSIRA-eligible. Courses run regularly — enroll early to secure your spot.', 7),
('contact,call,phone,number,reach,email,speak,talk',
 '📞 <strong>Contact Our Head Office:</strong><br>• WhatsApp: <strong>079 026 0098</strong> (Ally)<br>• Phone: <strong>015 001 2295</strong><br><br>Or <a href="contact.html" style="color:var(--gold);">visit our Contact page</a> to find your nearest branch (6 locations).', 8),
('cit,cash in transit,cash transit,money transport,cash',
 '💰 <strong>Cash In Transit (CIT):</strong><br>We offer professional CIT security services including:<br>• Armed escort teams<br>• Armoured vehicle coordination<br>• Route planning and threat assessment<br>• Emergency response protocols<br><br>CIT Training also available — R1,400. Enroll via our <a href="training.html" style="color:var(--gold);">Training page</a>.', 9),
('event,crowd,concert,gathering,festival,function,corporate',
 '🎪 <strong>Event Security Management:</strong><br>We provide comprehensive event security including:<br>• Crowd control and management<br>• Access control at all entry points<br>• VIP area security<br>• Roving patrols<br>• Incident response teams<br>• Post-event sweep<br><br>We''ve secured events ranging from 50 to 5,000+ attendees. Get a quote via WhatsApp.', 10),
('cctv,camera,surveillance,monitoring,install,setup',
 '📹 <strong>CCTV Installation:</strong><br>We install and configure professional-grade CCTV systems:<br>• Indoor and outdoor cameras<br>• HD &amp; night vision capability<br>• Remote monitoring integration<br>• DVR / NVR setup<br>• Cable routing and power backup<br><br>Contact us for a free site assessment and quote.', 11),
('training,course,grade,certificate,study',
 '📚 <strong>All Training Courses:</strong><br>• Grade A — R1,300<br>• Grade B — R1,200<br>• EDC — R2,400<br>• CIT — R1,400<br>• Reaction Unit — R1,400<br>• Handgun Private — R2,100<br>• Handgun/Shotgun/Rifle Business — R2,600<br><br>All PSIRA accredited. Visit our <a href="training.html" style="color:var(--gold);">Training page</a> for full details.', 12);

-- ---------------------------------------------------------------------
-- Leads captured from every quote/enrollment form on the site
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  source_page VARCHAR(50),          -- home | services | training | contact | vip | chatbot
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150),
  service_interest VARCHAR(150),
  location_text VARCHAR(150),
  message TEXT,
  status ENUM('new','contacted','won','lost') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Blog / News posts (new content type)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blog_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  excerpt VARCHAR(500),
  body LONGTEXT,
  cover_image VARCHAR(255),
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Gallery/video metadata (phase 4 — replaces per-folder meta.json)
-- Files stay on disk under vip/...; this table only stores metadata.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gallery_media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(120) NOT NULL,
  file_path VARCHAR(255) NOT NULL,   -- relative to site root, e.g. vip/CorporateEvents/foo.jpg
  media_type ENUM('image','video') NOT NULL DEFAULT 'image',
  title VARCHAR(150),
  description TEXT,
  event_date VARCHAR(60),
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
