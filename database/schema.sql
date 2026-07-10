-- =========================================================
-- schema.sql
-- Database schema for the portfolio site.
--
-- HOW TO USE:
--   mysql -u root -p < database/schema.sql
-- or import it through phpMyAdmin / your hosting control panel.
--
-- This creates the database, two tables (projects, contact_messages),
-- and seeds the projects table with the same sample data that ships
-- in data/projects.json, so you can compare the two approaches.
-- =========================================================

-- Force the connection charset to utf8mb4 for this import, regardless of
-- the client's default settings. Without this, special characters (em
-- dashes, curly quotes, non-English names) can get corrupted on import.
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS portfolio
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portfolio;

-- ---------------------------------------------------------
-- projects table
-- Replaces / supplements data/projects.json. Each row is one
-- project card shown on the site.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  tags VARCHAR(255) NOT NULL,        -- comma-separated, e.g. "PHP,MySQL,JavaScript"
  image VARCHAR(255) NOT NULL,       -- path to screenshot, e.g. assets/images/project-1.jpg
  live_url VARCHAR(255) DEFAULT NULL,
  github_url VARCHAR(255) DEFAULT NULL,
  sort_order INT DEFAULT 0,          -- lower numbers show first
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- admin_users table
-- Stores login credentials for the admin panel (php/admin/).
-- Passwords are hashed with PHP's password_hash() - never stored in plain text.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account: username "admin", password "ChangeMe123!"
-- IMPORTANT: log in and change this password immediately (Admin > Change Password).
INSERT INTO admin_users (username, password_hash) VALUES
('admin', '$2y$10$i9xYVu6NbIyUM48dD/K6F.O5790sMOemmm6JPTq8lqlQ1A8NrjsvW');

-- ---------------------------------------------------------
-- contact_messages table
-- Every validated contact form submission is stored here,
-- in addition to the text-file log kept as a backup.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- site_content table
-- Key/value store for editable text across the site (hero, about,
-- contact info, footer). Lets the admin panel change these without
-- touching index.php. Each row is one labeled field.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_content (
  content_key VARCHAR(60) PRIMARY KEY,
  content_value TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO site_content (content_key, content_value) VALUES
('site_title', 'Pemindu Prabodhitha — Portfolio'),
('site_description', 'Portfolio of Pemindu Prabodhitha — Web Developer. Projects, experience timeline, and contact info.'),
('nav_logo_first', 'PEMINDU'),
('nav_logo_last', 'PRABODHITHA'),

('hero_tag', '// Available for freelance & full-time roles'),
('hero_heading', 'Building things<br>for the <span class="accent">web</span>,<br>one project at a time.'),
('hero_lead', 'I''m <strong>Pemindu Prabodhitha</strong>, a web developer who designs and builds full-stack projects.'),
('hero_image', 'assets/images/edited.heic'),
('resume_path', 'assets/Brown And White Modern Resume Document A4-2.pdf'),

('about_heading', 'About Me'),
('about_bio', 'A motivated and analytically-driven undergraduate pursuing a BSc in Statistics and Computer Science at the University of Kelaniya. Possesses a strong foundation in statistical modeling, data analysis, and software development. Eager to apply interdisciplinary skills in data science, machine learning, or software engineering to solve real-world problems and contribute to innovative projects.'),
('about_photo', 'assets/images/IMG_9739.heic'),
('about_location', 'Kelaniya, Sri Lanka'),
('about_focus', 'Full-Stack Web Development'),
('about_currently', 'Open to opportunities'),

('contact_intro', 'Have a project in mind, a question, or just want to say hi? Fill out the form or reach me directly using the details below.'),
('contact_email', 'pprabodhitha0813@gmail.com'),
('contact_github', 'https://github.com/Pemindu-Prabodhitha'),
('contact_linkedin', 'https://linkedin.com/in/pemindu-prabodhitha-378712307'),
('contact_whatsapp', '+94704282988'),

('footer_name', 'Pemindu Prabodhitha');

-- ---------------------------------------------------------
-- timeline_items table
-- Each row is one entry in the Experience & Education timeline.
-- icon_type controls which icon renders on the timeline marker.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS timeline_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  organization VARCHAR(150) NOT NULL,
  date_range VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  icon_type ENUM('work', 'education') NOT NULL DEFAULT 'work',
  sort_order INT DEFAULT 0,           -- lower numbers show first (most recent first)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO timeline_items (title, organization, date_range, description, icon_type, sort_order) VALUES
('Under Graduate', 'University of Kelaniya', '2026 — Now', 'BSc in Statistics & Computer Science', 'education', 1),
('Trainee Assistant', 'People''s Bank', '2023 [May] — 2023 [September]', 'Business Promotion Officer (BPO) role at People''s Bank, Buttala Branch.', 'work', 2),
('O/L & A/L', 'Mo/Dutugemunu Central College', '2018 — 2022', 'Completed O/L & A/L.', 'education', 3);

-- ---------------------------------------------------------
-- skills table
-- Each row is one skill chip, grouped by category.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS skills (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(60) NOT NULL,      -- e.g. "Frontend", "Backend", "Tools", "Statistics"
  skill_name VARCHAR(100) NOT NULL,
  category_order INT DEFAULT 0,       -- controls the order categories appear in
  sort_order INT DEFAULT 0,           -- controls order within a category
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO skills (category, skill_name, category_order, sort_order) VALUES
('Frontend', 'HTML5', 1, 1),
('Frontend', 'CSS3', 1, 2),
('Frontend', 'JavaScript', 1, 3),
('Frontend', 'Responsive Design', 1, 4),
('Backend', 'PHP', 2, 1),
('Backend', 'MySQL', 2, 2),
('Backend', 'REST APIs', 2, 3),
('Tools', 'Git & GitHub', 3, 1),
('Tools', 'VS Code', 3, 2),
('Tools', 'Figma', 3, 3),
('Statistics', 'R Programming', 4, 1),
('Statistics', 'Power BI', 4, 2),
('Statistics', 'MS Excel', 4, 3);


-- Delete or edit these once you add your own projects.
-- ---------------------------------------------------------
INSERT INTO projects (title, description, tags, image, live_url, github_url, sort_order) VALUES
('Task Manager App',
 'A drag-and-drop task board with user accounts, built to practice full-stack CRUD operations and session-based authentication.',
 'PHP,MySQL,JavaScript',
 'assets/images/project-placeholder-1.svg',
 'https://example.com/task-manager',
 'https://github.com/yourusername/task-manager',
 1),

('Weather Dashboard',
 'Pulls live weather data from a public API and displays a 5-day forecast with animated icons and unit toggling.',
 'JavaScript,API,CSS',
 'assets/images/project-placeholder-2.svg',
 'https://example.com/weather-dashboard',
 'https://github.com/yourusername/weather-dashboard',
 2),

('Recipe Sharing Site',
 'A community recipe board where users can submit, rate, and filter recipes by category, stored in a MySQL database.',
 'PHP,MySQL,HTML/CSS',
 'assets/images/project-placeholder-3.svg',
 NULL,
 'https://github.com/yourusername/recipe-site',
 3),

('Personal Budget Tracker',
 'Tracks income and expenses with category breakdowns and a simple chart view, using vanilla JS and localStorage-free state.',
 'JavaScript,CSS',
 'assets/images/project-placeholder-4.svg',
 'https://example.com/budget-tracker',
 'https://github.com/yourusername/budget-tracker',
 4);
