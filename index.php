<?php
/**
 * index.php
 * Main portfolio page. Loads projects, timeline items, skills, and site
 * text content from the MySQL database if configured and reachable.
 * Falls back to hardcoded defaults (matching the original site content) for
 * anything the database can't provide, so the site never breaks.
 *
 * Requires a PHP server to run (see README.md).
 */

require_once __DIR__ . '/php/db.php';

// Small helper to escape output safely (for plain-text fields)
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$pdo = get_db_connection();

// ---------------------------------------------------------------------
// Site content (hero, about, contact, footer text) - key/value settings
// ---------------------------------------------------------------------
$siteContentDefaults = [
    'site_title' => 'Pemindu Prabodhitha — Portfolio',
    'site_description' => "Portfolio of Pemindu Prabodhitha — Web Developer. Projects, experience timeline, and contact info.",
    'nav_logo_first' => 'PEMINDU',
    'nav_logo_last' => 'PRABODHITHA',

    'hero_tag' => '// Available for freelance & full-time roles',
    'hero_heading' => 'Building things<br>for the <span class="accent">web</span>,<br>one project at a time.',
    'hero_lead' => "I'm <strong>Pemindu Prabodhitha</strong>, a web developer who designs and builds full-stack projects.",
    'hero_image' => 'assets/images/edited.heic',
    'resume_path' => 'assets/Brown And White Modern Resume Document A4-2.pdf',

    'about_heading' => 'About Me',
    'about_bio' => "A motivated and analytically-driven undergraduate pursuing a BSc in Statistics and Computer Science at the University of Kelaniya. Possesses a strong foundation in statistical modeling, data analysis, and software development. Eager to apply interdisciplinary skills in data science, machine learning, or software engineering to solve real-world problems and contribute to innovative projects.",
    'about_photo' => 'assets/images/IMG_9739.heic',
    'about_location' => 'Kelaniya, Sri Lanka',
    'about_focus' => 'Full-Stack Web Development',
    'about_currently' => 'Open to opportunities',

    'contact_intro' => "Have a project in mind, a question, or just want to say hi? Fill out the form or reach me directly using the details below.",
    'contact_email' => 'pprabodhitha0813@gmail.com',
    'contact_github' => 'https://github.com/Pemindu-Prabodhitha',
    'contact_linkedin' => 'https://linkedin.com/in/pemindu-prabodhitha-378712307',
    'contact_whatsapp' => '+94704282988',

    'footer_name' => 'Pemindu Prabodhitha',
];

$siteContent = $siteContentDefaults;

if ($pdo !== null) {
    try {
        $rows = $pdo->query('SELECT content_key, content_value FROM site_content')->fetchAll();
        foreach ($rows as $row) {
            $siteContent[$row['content_key']] = $row['content_value'];
        }
    } catch (PDOException $e) {
        error_log('site_content query failed, using defaults: ' . $e->getMessage());
    }
}

// c() = escaped plain-text field. c_html() = raw output for the handful of
// fields that intentionally allow a few safe inline tags (sanitized on save
// in the admin panel - see php/admin/content.php).
function c($key) {
    global $siteContent;
    return e($siteContent[$key] ?? '');
}
function c_html($key) {
    global $siteContent;
    return $siteContent[$key] ?? '';
}

// ---------------------------------------------------------------------
// Timeline items (Experience & Education)
// ---------------------------------------------------------------------
$timelineDefaults = [
    ['title' => 'Under Graduate', 'organization' => 'University of Kelaniya', 'date_range' => '2026 — Now', 'description' => 'BSc in Statistics & Computer Science', 'icon_type' => 'education'],
    ['title' => 'Trainee Assistant', 'organization' => "People's Bank", 'date_range' => '2023 [May] — 2023 [September]', 'description' => "Business Promotion Officer (BPO) role at People's Bank, Buttala Branch.", 'icon_type' => 'work'],
    ['title' => 'O/L & A/L', 'organization' => 'Mo/Dutugemunu Central College', 'date_range' => '2018 — 2022', 'description' => 'Completed O/L & A/L.', 'icon_type' => 'education'],
];

$timelineItems = $timelineDefaults;

if ($pdo !== null) {
    try {
        $rows = $pdo->query('SELECT * FROM timeline_items ORDER BY sort_order ASC, id ASC')->fetchAll();
        if (!empty($rows)) {
            $timelineItems = $rows;
        }
    } catch (PDOException $e) {
        error_log('timeline_items query failed, using defaults: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------
// Skills, grouped by category. Each skill is ['name' => ..., 'icon' => ...]
// ---------------------------------------------------------------------
$skillsDefaults = [
    'Frontend' => [
        ['name' => 'HTML5', 'icon' => 'assets/icons/html5.svg'],
        ['name' => 'CSS3', 'icon' => 'assets/icons/css3.svg'],
        ['name' => 'JavaScript', 'icon' => 'assets/icons/javascript.svg'],
        ['name' => 'Responsive Design', 'icon' => 'assets/icons/responsive.svg'],
    ],
    'Backend' => [
        ['name' => 'PHP', 'icon' => 'assets/icons/php.svg'],
        ['name' => 'MySQL', 'icon' => 'assets/icons/mysql.svg'],
        ['name' => 'REST APIs', 'icon' => 'assets/icons/rest-api.svg'],
    ],
    'Tools' => [
        ['name' => 'Git', 'icon' => 'assets/icons/git.svg'],
        ['name' => 'GitHub', 'icon' => 'assets/icons/github.svg'],
        ['name' => 'VS Code', 'icon' => 'assets/icons/vscode.svg'],
        ['name' => 'Figma', 'icon' => 'assets/icons/figma.svg'],
    ],
    'Statistics' => [
        ['name' => 'R Programming', 'icon' => 'assets/icons/r-lang.svg'],
        ['name' => 'Power BI', 'icon' => 'assets/icons/bi-dashboard.svg'],
        ['name' => 'MS Excel', 'icon' => 'assets/icons/spreadsheet.svg'],
    ],
];

$skillsByCategory = $skillsDefaults;

if ($pdo !== null) {
    try {
        $rows = $pdo->query('SELECT * FROM skills ORDER BY category_order ASC, category ASC, sort_order ASC, id ASC')->fetchAll();
        if (!empty($rows)) {
            $skillsByCategory = [];
            foreach ($rows as $row) {
                $skillsByCategory[$row['category']][] = [
                    'name' => $row['skill_name'],
                    'icon' => $row['icon'] ?? '',
                ];
            }
        }
    } catch (PDOException $e) {
        error_log('skills query failed, using defaults: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------
// Projects
// ---------------------------------------------------------------------
$projects = [];
$dataSource = 'json'; // for a small on-page note about which source is active

if ($pdo !== null) {
    try {
        $stmt = $pdo->query('SELECT * FROM projects ORDER BY sort_order ASC, id ASC');
        $rows = $stmt->fetchAll();

        // Normalize DB rows to the same shape as the JSON file (tags as an
        // array, live/github keys) so the rest of the page doesn't need to
        // care which source the data came from.
        foreach ($rows as $row) {
            $projects[] = [
                'title' => $row['title'],
                'description' => $row['description'],
                'tags' => array_filter(array_map('trim', explode(',', $row['tags']))),
                'image' => $row['image'],
                'live' => $row['live_url'],
                'github' => $row['github_url'],
            ];
        }
        $dataSource = 'database';
    } catch (PDOException $e) {
        // Table might not exist yet (schema.sql not imported) - fall back to JSON
        error_log('Project query failed, falling back to JSON: ' . $e->getMessage());
    }
}

// Fallback: read from the JSON file if the database wasn't used or returned nothing
if (empty($projects)) {
    $projectsFile = __DIR__ . '/data/projects.json';
    if (file_exists($projectsFile)) {
        $json = file_get_contents($projectsFile);
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $projects = $decoded;
            $dataSource = 'json';
        }
    }
}

// Collect a unique, sorted list of tags across all projects for the filter bar
$allTags = [];
foreach ($projects as $project) {
    foreach ($project['tags'] as $tag) {
        $allTags[$tag] = true;
    }
}
$allTags = array_keys($allTags);
sort($allTags);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo c('site_title'); ?></title>
<meta name="description" content="<?php echo c('site_description'); ?>">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ============ NAVIGATION ============ -->
<nav class="navbar">
  <div class="nav-inner">
    <a href="#top" class="logo"><?php echo c('nav_logo_first'); ?><span>.</span><?php echo c('nav_logo_last'); ?></a>
    <ul class="nav-links" id="navLinks">
      <li><a href="#about"> About</a></li>
      <li><a href="#timeline"> Timeline</a></li>
      <li><a href="#projects"> Projects</a></li>
      <li><a href="#skills"> Skills</a></li>
      <li><a href="#contact"> Contact</a></li>
    </ul>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- ============ HERO ============ -->
<header class="hero" id="top">
  <div class="container hero-grid">
    <div>
      <span class="hero-tag"><?php echo c('hero_tag'); ?></span>
      <h1><?php echo c_html('hero_heading'); ?></h1>
      <p class="lead">
        <?php echo c_html('hero_lead'); ?>
      </p>
      <div class="hero-actions">
        <a href="#projects" class="btn btn-primary">View Projects</a>
        <a href="<?php echo e($siteContent['resume_path']); ?>" class="btn btn-outline" download>Download CV</a>
      </div>
    </div>
    <div class="hero-schematic bracket">
      <span class="tag-tl"></span>
      <img src="<?php echo c('hero_image'); ?>" class="hero-img" alt="<?php echo c('nav_logo_first'); ?> <?php echo c('nav_logo_last'); ?>" onerror="this.onerror=null;this.src='assets/images/profile-placeholder.svg';">
      <span class="tag-br"></span>
    </div>
  </div>
</header>

<!-- ============ ABOUT ============ -->
<section id="about">
  <div class="container about-grid">
    <div class="about-photo reveal-up">
      <img src="<?php echo c('about_photo'); ?>" alt="Profile picture" onerror="this.onerror=null;this.src='assets/images/about-placeholder.svg';">
    </div>
    <div class="about-text reveal-up">
      <span class="eyebrow">About Me</span>
      <h2 class="section-heading"><?php echo c('about_heading'); ?></h2>
      <p><?php echo nl2br(c('about_bio')); ?></p>
      <dl class="quick-facts">
        <div>
          <dt>Location</dt>
          <dd><?php echo c('about_location'); ?></dd>
        </div>
        <div>
          <dt>Focus</dt>
          <dd><?php echo c('about_focus'); ?></dd>
        </div>
        <div>
          <dt>Currently</dt>
          <dd><?php echo c('about_currently'); ?></dd>
        </div>
        <div>
          <dt>Email</dt>
          <dd><?php echo c('contact_email'); ?></dd>
        </div>
      </dl>
    </div>
  </div>
</section>

<!-- ============ TIMELINE ============ -->
<section id="timeline">
  <div class="container">
    <span class="eyebrow">Experience &amp; Education</span>
    <h2 class="section-heading">Where I've been</h2>

    <div class="timeline-v2">
      <?php foreach ($timelineItems as $i => $item): ?>
        <?php $side = $i % 2 === 0 ? 'left' : 'right'; ?>
        <div class="timeline-entry timeline-entry--<?php echo $side; ?>">
          <div class="timeline-marker">
            <?php if ($item['icon_type'] === 'education'): ?>
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5" stroke-linecap="round"/></svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php endif; ?>
          </div>
          <article class="timeline-card">
            <div class="timeline-card-head">
              <div class="timeline-logo">
                <?php if ($item['icon_type'] === 'education'): ?>
                  <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="var(--blueprint-deep)" stroke-width="1.8"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5" stroke-linecap="round"/></svg>
                <?php else: ?>
                  <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="var(--blueprint-deep)" stroke-width="1.8"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?php endif; ?>
              </div>
              <div>
                <h3><?php echo e($item['title']); ?></h3>
                <p class="timeline-org"><?php echo e($item['organization']); ?></p>
              </div>
            </div>

            <div class="timeline-meta">
              <span>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round"/></svg>
                <?php echo e($item['date_range']); ?>
              </span>
            </div>

            <details class="timeline-disclosure">
              <summary class="timeline-toggle">
                <span class="label-more">Show More</span>
                <span class="label-less">Show Less</span>
                <svg class="toggle-chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </summary>
              <div class="timeline-details">
                <p><?php echo e($item['description']); ?></p>
              </div>
            </details>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ PROJECTS ============ -->
<!-- Project data source: <?php echo e($dataSource); ?> (database or json fallback) -->
<section id="projects">
  <div class="container">
    <span class="eyebrow">Selected Work</span>
    <h2 class="section-heading">Projects</h2>

    <div class="filter-bar" id="filterBar">
      <button class="filter-btn active" data-filter="all">All</button>
      <?php foreach ($allTags as $tag): ?>
        <button class="filter-btn" data-filter="<?php echo e($tag); ?>"><?php echo e($tag); ?></button>
      <?php endforeach; ?>
    </div>

    <div class="project-grid" id="projectGrid">
      <?php if (empty($projects)): ?>
        <p>No projects found.</p>
      <?php else: ?>
        <?php foreach ($projects as $index => $project): ?>
          <article class="project-card" data-tags="<?php echo e(implode(',', $project['tags'])); ?>">
            <span class="project-spec">PROJECT_<?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
            <div class="project-thumb">
              <img src="<?php echo e($project['image']); ?>" alt="<?php echo e($project['title']); ?> screenshot">
            </div>
            <h3><?php echo e($project['title']); ?></h3>
            <p><?php echo e($project['description']); ?></p>
            <div class="tag-list">
              <?php foreach ($project['tags'] as $tag): ?>
                <span class="tag-chip"><?php echo e($tag); ?></span>
              <?php endforeach; ?>
            </div>
            <div class="project-links">
              <?php if (!empty($project['live'])): ?>
                <a href="<?php echo e($project['live']); ?>" target="_blank" rel="noopener">Live Demo →</a>
              <?php endif; ?>
              <?php if (!empty($project['github'])): ?>
                <a href="<?php echo e($project['github']); ?>" target="_blank" rel="noopener">Source Code →</a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ SKILLS ============ -->
<section id="skills">
  <div class="container">
    <span class="eyebrow">Toolbox</span>
    <h2 class="section-heading">Skills</h2>

    <div class="skills-groups">
      <?php foreach ($skillsByCategory as $category => $skillList): ?>
        <div class="skills-group">
          <h3><?php echo e($category); ?></h3>
          <ul>
            <?php foreach ($skillList as $skill): ?>
              <?php
                // Support both the new ['name'=>..,'icon'=>..] shape and a
                // plain string, in case older custom data is still around.
                $skillName = is_array($skill) ? $skill['name'] : $skill;
                $skillIcon = is_array($skill) ? ($skill['icon'] ?? '') : '';
              ?>
              <li>
                <?php if (!empty($skillIcon)): ?>
                  <img
                    src="<?php echo e($skillIcon); ?>"
                    alt=""
                    class="skill-icon"
                    loading="lazy"
                    onerror="this.style.display='none';"
                  >
                <?php endif; ?>
                <span><?php echo e($skillName); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ CONTACT ============ -->
<section id="contact">
  <div class="container contact-grid">
    <div class="contact-info reveal-up">
      <span class="eyebrow">Get In Touch</span>
      <h2 class="section-heading">Let's work together</h2>
      <p><?php echo c('contact_intro'); ?></p>
      <ul class="contact-methods">
        <li><span class="contact-icon"><a href="mailto:<?php echo c('contact_email'); ?>"><img src='assets/images/Mail Icon.jpg'></a></span><span class="label">Email</span> <a href="mailto:<?php echo c('contact_email'); ?>"><?php echo c('contact_email'); ?></a></li>
        <li><span class="contact-icon"><a href="<?php echo c('contact_github'); ?>"><img src='assets/images/Download GitHub Logo, Git Hub Icon On White Background.jpg'></a></span><span class="label">GitHub</span> <a href="<?php echo c('contact_github'); ?>" target="_blank" rel="noopener"><?php echo c('contact_github'); ?></a></li>
        <li><span class="contact-icon"><a href="<?php echo c('contact_linkedin'); ?>"><img src='assets/images/Square linkedin logo isolated on white background | Premium Vector.jpg'></a></span><span class="label">LinkedIn</span> <a href="<?php echo c('contact_linkedin'); ?>" target="_blank" rel="noopener"><?php echo c('contact_linkedin'); ?></a></li>
        <li><span class="contact-icon"><a href="https://wa.me/<?php echo e(ltrim($siteContent['contact_whatsapp'], '+')); ?>"><img src='assets/images/иконка ватсап.jpg'></a></span><span class="label">WhatsApp</span> <a href="https://wa.me/<?php echo e(ltrim($siteContent['contact_whatsapp'], '+')); ?>" target="_blank" rel="noopener"><?php echo c('contact_whatsapp'); ?></a></li>
      </ul>
    </div>

    <form class="contact-form reveal-up" id="contactForm" novalidate>
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required autocomplete="name">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="5" required></textarea>
      </div>

      <!-- Honeypot field: hidden from real users, bots often fill it in -->
      <div class="form-group hp-field" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-status" id="formStatus" role="status" aria-live="polite"></div>

      <button type="submit" class="btn btn-primary submit-btn" id="submitBtn">Send Message</button>
    </form>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer>
  <ul>
    <li><a href="https://github.com/Pemindu-Prabodhitha"><img src='assets/images/Download GitHub Logo, Git Hub Icon On White Background.jpg' href="<?php echo c('contact_github'); ?>" target="_blank" rel="noopener"></a></li>
    <li><a href="https://linkedin.com/in/pemindu-prabodhitha-378712307"><img src='assets/images/ -5.jpg'></a></li>
    <li><a href="https://wa.me/94704282988"><img src='assets/images/иконка ватсап.jpg'></a></li>
    <li><a href="https://www.facebook.com/share/198gf97GNH/?mibextid=wwXIfr"><img src='assets/images/Facebook Logo Icon Vector & Transparent PNG.jpg'></a></li>
    <li><a href="https://www.instagram.com/_pemindu_?igsh=Z3RnMWxxc24wMHdh&utm_source=qr"><img src='assets/images/Kapwing_ Make a Video About Anything.jpg'></a></li>
  </ul>
  <div class="container">
    <h6>&copy; <?php echo date('Y'); ?> <?php echo c('footer_name');?>.All Rights Reserved.</h6>
  </div>
</footer>

<button class="back-to-top" id="backToTop" aria-label="Back to top">↑</button>

<script src="js/main.js"></script>
</body>
</html>
