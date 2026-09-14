-- Database Creation for Reiki Website
CREATE DATABASE IF NOT EXISTS `reiki_website` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `reiki_website`;

-- 1. admin_users
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. services
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `short_description` TEXT,
  `full_description` LONGTEXT,
  `price` DECIMAL(10,2) NULL DEFAULT NULL,
  `duration_minutes` INT NOT NULL DEFAULT 60,
  `image` VARCHAR(255) NULL,
  `sort_order` INT DEFAULT 0,
  `is_free` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. courses
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `short_description` TEXT,
  `full_description` LONGTEXT,
  `price_text` VARCHAR(100) DEFAULT 'Contact for price',
  `image` VARCHAR(255) NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. products
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `short_description` TEXT,
  `full_description` LONGTEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `original_price` DECIMAL(10,2) NULL,
  `discount_percent` INT DEFAULT 0,
  `badge_text` VARCHAR(100) DEFAULT 'Reiki Charged',
  `image` VARCHAR(255) NULL,
  `additional_images` JSON NULL,
  `category` VARCHAR(100) DEFAULT 'Bracelets',
  `in_stock` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. team_members
DROP TABLE IF EXISTS `team_members`;
CREATE TABLE `team_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `role` VARCHAR(100) NOT NULL,
  `title` VARCHAR(150) NULL,
  `bio` TEXT NULL,
  `specialties` JSON NULL,
  `image` VARCHAR(255) NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. testimonials
DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE `testimonials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `client_name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100) NULL,
  `content` TEXT NOT NULL,
  `rating` INT DEFAULT 5,
  `image` VARCHAR(255) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. gallery_images
DROP TABLE IF EXISTS `gallery_images`;
CREATE TABLE `gallery_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `image_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. blog_posts
DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE `blog_posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `excerpt` TEXT NULL,
  `content` TEXT NOT NULL,
  `image` VARCHAR(255) NULL,
  `author` VARCHAR(100) DEFAULT 'Admin',
  `tags` JSON NULL,
  `is_published` TINYINT(1) DEFAULT 1,
  `published_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. contact_inquiries
DROP TABLE IF EXISTS `contact_inquiries`;
CREATE TABLE `contact_inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. bracelet_orders
DROP TABLE IF EXISTS `bracelet_orders`;
CREATE TABLE `bracelet_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_type` ENUM('birth-chart', 'customized') NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `whatsapp` VARCHAR(30) NULL,
  `date_of_birth` DATE NULL,
  `time_of_birth` TIME NULL,
  `place_of_birth` VARCHAR(150) NULL,
  `intention` VARCHAR(255) NULL,
  `message` TEXT NULL,
  `status` ENUM('pending', 'contacted', 'completed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. site_stats
DROP TABLE IF EXISTS `site_stats`;
CREATE TABLE `site_stats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `stat_key` VARCHAR(100) NOT NULL UNIQUE,
  `stat_value` VARCHAR(100) NOT NULL,
  `label` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. site_settings
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. login_attempts (Rate limiting & brute force defense)
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_successful` TINYINT(1) DEFAULT 0,
  INDEX `idx_ip_attempt` (`ip_address`, `attempted_at`),
  INDEX `idx_user_attempt` (`username`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- SEED DATA INSERTIONS
-- =========================================================================

-- 1. admin_users seed (username: admin, password: hashed "admin123")
INSERT INTO `admin_users` (`username`, `password`, `email`) VALUES
('admin', '$2y$10$1w3Tykob83YWB96urvE8neBY9CG25IAtVxI/lfB1LjVBThYt9umR6', 'admin@reikiwebsite.com');

-- 2. services seed (all 6 services)
INSERT INTO `services` (`title`, `slug`, `short_description`, `full_description`, `price`, `duration_minutes`, `image`, `sort_order`, `is_free`, `is_active`) VALUES
('Reiki Healing Session', 'reiki-healing-session', 'Personalized hands-on or gentle touch energy balancing session to release blockages and promote overall physical and emotional well-being.', 'Experience the transformative power of authentic Usui Reiki. During this session, the healer channels universal life force energy into your body, targeting energy centers (chakras) to alleviate stress, anxiety, and physical discomfort. Ideal for deep relaxation and holistic recovery.', 1500.00, 60, 'assets/images/services/reiki-healing.jpg', 1, 0, 1),
('Distance Reiki Healing', 'distance-reiki-healing', 'Experience powerful remote energy healing from the comfort of your home, regardless of geographical distance.', 'Energy is not restricted by time or space. Distance Reiki utilizes quantum resonance techniques to deliver subtle energy healing directly to you. Includes a pre-session intention discussion and post-session feedback report via phone or video call.', 1200.00, 45, 'assets/images/services/distance-reiki.jpg', 2, 0, 1),
('Chakra Balancing & Alignment', 'chakra-balancing', 'Harmonize and align your 7 major chakras to restore emotional clarity, mental focus, and vitality.', 'Blocked or misaligned chakras can cause emotional turmoil, chronic fatigue, and physical disharmony. This specialized session uses focused energy channels, tuned singing bowls, and crystal resonance to cleanse, balance, and re-energize each chakra center.', 1800.00, 60, 'assets/images/services/chakra-balancing.jpg', 3, 0, 1),
('Aura Cleansing & Repair', 'aura-cleansing', 'Clear negative attachments, energetic debris, and repair holes in your aura field.', 'Daily stress and negative environments can cause energetic tears and dark spots in your auric field. Our Master Healers sweep away negative vibrations, seal auric leaks, and build a protective luminous shield around your subtle body.', 2000.00, 75, 'assets/images/services/aura-cleansing.jpg', 4, 0, 1),
('Crystal Energy Therapy', 'crystal-energy-therapy', 'Synergistic combination of pure crystal frequencies and Reiki energy for deep cellular transformation.', 'Sacred geometry and high-vibrational gemstone layouts are placed on key points of your body while channeling Reiki energy. The amplified frequencies help accelerate physical healing, calm an overactive mind, and elevate spiritual consciousness.', 2500.00, 90, 'assets/images/services/crystal-therapy.jpg', 5, 0, 1),
('Free Initial Consultation', 'free-consultation', 'A complimentary 20-minute consultation to assess your energy needs and recommend custom healing pathways.', 'Unsure which healing modality is right for you? Book a free, no-obligation session with one of our expert practitioners. We will analyze your current energetic state, answer questions, and tailor a personalized healing plan.', NULL, 20, 'assets/images/services/free-consultation.jpg', 6, 1, 1);

-- 3. courses seed (5 courses)
INSERT INTO `courses` (`title`, `slug`, `short_description`, `full_description`, `price_text`, `image`, `sort_order`, `is_active`) VALUES
('Reiki Level 1: First Degree (Shoden)', 'reiki-level-1-shoden', 'Learn the basics of Reiki energy healing, self-treatment techniques, and receive your First Degree attunement.', 'Begin your spiritual journey into energy healing. Level 1 focuses on self-healing, physical body alignment, and understanding universal life force energy. You will receive 4 sacred attunements, practical training manuals, and a recognized practitioner certificate.', '₹4,999', 'assets/images/courses/reiki-level-1.jpg', 1, 1),
('Reiki Level 2: Second Degree (Okuden)', 'reiki-level-2-okuden', 'Master sacred Reiki symbols, distance healing techniques, and mental/emotional healing methods.', 'Deepen your healing channel with Second Degree training. Learn three sacred Usui Reiki symbols for power, emotional/mental healing, and distance transmission. Enables you to practice professionally and heal others remotely.', '₹7,999', 'assets/images/courses/reiki-level-2.jpg', 2, 1),
('Reiki Level 3A: Master Practitioner (Shinpiden)', 'reiki-level-3a-master-practitioner', 'Unlock the Master Symbol, spiritual empowerment techniques, and advanced energy grid work.', 'Step into Mastership. This course introduces the Master Symbol (Dai Ko Myo), advanced psychic surgery, crystal Reiki grids, and deep spiritual attunements designed for practitioners seeking maximum healing potency.', '₹14,999', 'assets/images/courses/reiki-level-3a.jpg', 3, 1),
('Reiki Level 3B: Grandmaster Teacher (Shihan)', 'reiki-level-3b-master-teacher', 'Comprehensive teacher training to pass attunements and teach Usui Reiki Level 1 to Master Level.', 'Designed for dedicated masters called to teach. Learn how to perform sacred attunement ceremonies, structure courses, guide students, and run a successful spiritual healing academy. Includes full teaching rights and syllabus access.', 'Contact for price', 'assets/images/courses/reiki-level-3b.jpg', 4, 1),
('Crystal & Chakra Energy Specialist', 'crystal-chakra-energy-specialist', 'Specialized certification covering gemstone frequencies, grid creation, and advanced chakra therapy.', 'Master the art of combining natural crystals with energy work. Learn gemstone identification, cleansing, programming, and specialized body layout techniques for targeted emotional and physical healing.', '₹9,999', 'assets/images/courses/crystal-specialist.jpg', 5, 1);

-- 4. products seed (sample products)
INSERT INTO `products` (`title`, `slug`, `short_description`, `full_description`, `price`, `original_price`, `discount_percent`, `badge_text`, `image`, `additional_images`, `category`, `in_stock`, `sort_order`, `is_active`) VALUES
('Reiki Charged Amethyst Healing Bracelet', 'reiki-charged-amethyst-bracelet', '100% natural Amethyst crystal beads infused with high-frequency Reiki energy for stress relief and intuition.', 'Handcrafted with genuine 8mm natural grade-A Amethyst beads. Each bracelet is cleansed with sage and energized through an intensive Reiki Master ritual. Promotes peace, relieves anxiety, and enhances meditative states.', 1299.00, 1999.00, 35, 'Reiki Charged', 'assets/images/products/amethyst-bracelet.jpg', '["assets/images/products/amethyst-1.jpg", "assets/images/products/amethyst-2.jpg"]', 'Crystal Bracelets', 1, 1, 1),
('7 Chakra Balance Natural Gemstone Bracelet', '7-chakra-balance-bracelet', 'Seven sacred natural gemstones harmonized to align and energize all seven chakras continuously.', 'Features Lava Stone, Red Jasper, Carnelian, Tiger Eye, Green Aventurine, Sodalite, and Amethyst. Designed to absorb negative energies and maintain steady chakra alignment throughout your busy day.', 1499.00, 2199.00, 32, 'Best Seller', 'assets/images/products/7-chakra-bracelet.jpg', '["assets/images/products/chakra-1.jpg"]', 'Chakra Bracelets', 1, 2, 1),
('Rose Quartz Divine Love & Harmony Bracelet', 'rose-quartz-love-bracelet', 'Attract unconditional love, heal emotional wounds, and open your heart chakra with pure Rose Quartz.', 'Infused with heart-centered Reiki vibrations. Encourages self-love, compassion, forgiveness, and romantic harmony. Features high-clarity natural Rose Quartz with a polished silver lotus charm.', 1199.00, 1799.00, 33, 'Heart Chakra', 'assets/images/products/rose-quartz-bracelet.jpg', '["assets/images/products/rosequartz-1.jpg"]', 'Crystal Bracelets', 1, 3, 1),
('Black Tourmaline Ultimate Protection Bracelet', 'black-tourmaline-protection-bracelet', 'Powerful psychic shield bracelet crafted from authentic Black Tourmaline for grounding and protection.', 'Black Tourmaline is the premier stone of psychic defense and electromagnetic radiation grounding. Charged with protective Reiki symbols to ward off unwanted energies and negative vibrations.', 1399.00, 1999.00, 30, 'Protection', 'assets/images/products/black-tourmaline-bracelet.jpg', '["assets/images/products/tourmaline-1.jpg"]', 'Protection Bracelets', 1, 4, 1);

-- 5. team_members seed (4 members)
INSERT INTO `team_members` (`name`, `role`, `title`, `bio`, `specialties`, `image`, `sort_order`, `is_active`) VALUES
('Dr. Ananya Sharma', 'Founder & Grandmaster Healer', 'Usui Reiki Grandmaster & Spiritual Mentor', 'With over 25 years of dedicated spiritual practice, Dr. Ananya has initiated thousands of students into Usui Reiki. She specializes in deep cellular trauma recovery, karmic clearing, and high-frequency energy attunements.', '["Usui Reiki Grandmaster", "Karmic Clearing", "Subtle Energy Medicine", "Chakra Master"]', 'assets/images/team/ananya-sharma.jpg', 1, 1),
('Rajesh Varma', 'Senior Reiki Master', 'Chakra & Aura Specialist', 'Rajesh brings 15 years of holistic healing experience. He combines traditional Indian Pranic techniques with Usui Reiki to deliver powerful aura repairs and distance healing sessions.', '["Distance Reiki", "Aura Repair", "Pranic Healing", "Stress Reduction"]', 'assets/images/team/rajesh-varma.jpg', 2, 1),
('Priya Nair', 'Holistic Energy Practitioner', 'Crystal Healing & Meditation Master', 'Priya is a certified Crystal Reiki Practitioner and sound therapist. Her intuitive sessions blend sacred gemstone energy with calming vocal chants to facilitate emotional release.', '["Crystal Therapy", "Sound Healing", "Emotional Release", "Meditation Guidance"]', 'assets/images/team/priya-nair.jpg', 3, 1),
('Vikramaditya Singh', 'Vedic Astrologer & Energy Consultant', 'Astrological Energy Alignment Specialist', 'Vikramaditya combines Vedic astrology with gemstone energy alignment. He designs personalized birth-chart bracelets tailored to balance planetary influences and strengthen personal aura.', '["Vedic Astrology", "Planetary Gemstones", "Custom Birth Chart Alignment"]', 'assets/images/team/vikramaditya-singh.jpg', 4, 1);

-- 6. testimonials seed (3 testimonials)
INSERT INTO `testimonials` (`client_name`, `location`, `content`, `rating`, `image`, `is_active`) VALUES
('Sunita Mehta', 'Mumbai, India', 'My distance Reiki session with Dr. Ananya was truly life-changing. I was feeling extremely stressed and exhausted for months. After just 2 sessions, I felt an overwhelming sense of peace and mental clarity. Highly recommended!', 5, 'assets/images/testimonials/sunita.jpg', 1),
('Rahul Kapoor', 'Delhi, India', 'I completed my Reiki Level 1 & 2 courses here. The depth of teaching, attunement quality, and personal guidance from the team were exceptional. The course changed my perspective on energy completely.', 5, 'assets/images/testimonials/rahul.jpg', 1),
('Kavita Reddy', 'Bengaluru, India', 'Ordered the custom birth-chart bracelet and had a distance chakra balancing session. The bracelet feels so energetic, and my chronic anxiety has decreased significantly. Wonderful experience!', 5, 'assets/images/testimonials/kavita.jpg', 1);

-- 7. gallery_images seed
INSERT INTO `gallery_images` (`image_path`, `caption`, `category`, `sort_order`, `is_active`) VALUES
('assets/images/gallery/healing-room.jpg', 'Serene Reiki Healing Sanctuary', 'Sanctuary', 1, 1),
('assets/images/gallery/attunement-ceremony.jpg', 'Reiki Master Attunement Ceremony', 'Workshops', 2, 1),
('assets/images/gallery/crystal-grids.jpg', 'Sacred Geometry & Crystal Energy Layouts', 'Crystals', 3, 1),
('assets/images/gallery/meditation-group.jpg', 'Guided Group Chakra Meditation Session', 'Events', 4, 1);

-- 8. blog_posts seed
INSERT INTO `blog_posts` (`title`, `slug`, `excerpt`, `content`, `image`, `author`, `tags`, `is_published`, `published_at`) VALUES
('Understanding the 7 Major Chakras and How Reiki Restores Harmony', 'understanding-7-major-chakras-reiki', 'Discover how blocked chakras affect physical and emotional health, and how Reiki energy restores natural equilibrium.', 'Chakras are energy centers throughout your body that govern physical organs and emotional well-being. When stress, trauma, or negative environments block these vortexes, physical ailments and mental fatigue follow. Usui Reiki directs high-vibrational universal life force to dissolve these blockages, allowing energy to flow freely once again.', 'assets/images/blog/chakras-guide.jpg', 'Dr. Ananya Sharma', '["Chakras", "Reiki Healing", "Energy Flow"]', 1, NOW()),
('The Science & Spirituality Behind Reiki Charged Crystal Bracelets', 'science-spirituality-reiki-charged-bracelets', 'Explore how natural gemstones amplify intentions and hold energetic programming when cleansed and charged by a Reiki Master.', 'Crystals possess stable crystalline lattice structures that naturally vibrate at precise frequencies. When combined with sacred Reiki symbols during an attunement ritual, gemstones act as continuous transmitters of peaceful, protective, and restorative energy.', 'assets/images/blog/crystal-bracelets.jpg', 'Priya Nair', '["Crystals", "Bracelets", "Reiki Charged"]', 1, NOW());

-- 9. site_stats seed
INSERT INTO `site_stats` (`stat_key`, `stat_value`, `label`) VALUES
('lives_healed', '30000', 'Lives Healed & Transformed'),
('years_experience', '25', 'Years of Experience'),
('course_levels', '6', 'Reiki & Healing Course Levels'),
('sessions_completed', '25000', 'Healing Sessions Completed');

-- 10. site_settings seed
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Divine Reiki & Energy Healing Center'),
('phone', '+91 98765 43210'),
('email', 'info@reikiwebsite.com'),
('address', '108 Healing Touch Way, Spiritual Enclave, New Delhi - 110001'),
('whatsapp', '+91 98765 43210'),
('facebook_url', 'https://facebook.com/divinereiki'),
('youtube_url', 'https://youtube.com/c/divinereiki'),
('maps_url', 'https://maps.google.com/?q=Reiki+Center'),
('working_hours', 'Monday - Saturday: 9:00 AM - 7:00 PM (IST)');

-- 11. chat_sessions table
CREATE TABLE IF NOT EXISTS `chat_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_token` VARCHAR(64) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `pending_form` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_active_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_session_token` (`session_token`),
    INDEX `idx_ip_created` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 12. chat_messages table
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT NOT NULL,
    `role` ENUM('user', 'assistant') NOT NULL,
    `content` TEXT NOT NULL,
    `was_blocked` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_session_messages` (`session_id`, `created_at`),
    INDEX `idx_was_blocked` (`was_blocked`),
    CONSTRAINT `fk_chat_messages_session` 
        FOREIGN KEY (`session_id`) 
        REFERENCES `chat_sessions` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


