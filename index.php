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

    'dark_mode' => '0',
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
                'presentation' => $row['presentation_path'] ?? '',
                'video_path' => $row['video_path'] ?? '',
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

$isDarkMode = ($siteContent['dark_mode'] ?? '0') === '1';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $isDarkMode ? 'dark' : 'light'; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo c('site_title'); ?></title>
<meta name="description" content="<?php echo c('site_description'); ?>">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/dark-mode.css">
</head>
<body>

<!-- ============ NAVIGATION ============ -->
<nav class="navbar">
  <div class="nav-inner">
    <a href="#top" class="logo"><?php echo c('nav_logo_first'); ?><span>.</span><?php echo c('nav_logo_last'); ?></a>
    <ul class="nav-links" id="navLinks">
      <li><a href="#about">About</a></li>
      <li><a href="#timeline">Timeline</a></li>
      <li><a href="#projects">Projects</a></li>
      <li><a href="#skills">Skills</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
      </button>
      <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<!-- ============ HERO ============ -->
<header class="hero" id="top">
  <canvas id="particleCanvas"></canvas>
  <div class="container hero-grid">
    <div>
      <span class="hero-tag"><?php echo c('hero_tag'); ?></span>
      <h1>
        <?php echo c_html('hero_heading'); ?>
        <br><span class="hero-role"><span id="typingText"></span><span class="typing-cursor"></span></span>
      </h1>
      <p class="lead">
        <?php echo c_html('hero_lead'); ?>
      </p>
      <div class="hero-actions">
        <a href="#projects" class="btn btn-primary">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12h18M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          View Projects
        </a>
        <a href="<?php echo e($siteContent['resume_path']); ?>" class="btn btn-outline" download>
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3v12M8 11l4 4 4-4M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Download CV
        </a>
      </div>
      <div class="hero-badges">
        <span class="hero-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          BSc Statistics &amp; CS
        </span>
        <span class="hero-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Open to Opportunities
        </span>
        <span class="hero-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Kelaniya, Sri Lanka
        </span>
      </div>
    </div>
    <div class="hero-schematic">
      <div class="hero-img-wrapper">
        <img src="<?php echo c('hero_image'); ?>" class="hero-img" alt="<?php echo c('nav_logo_first'); ?> <?php echo c('nav_logo_last'); ?>"
          onerror="this.onerror=null;this.src='assets/images/profile-placeholder.svg';">
      </div>
    </div>
  </div>

  <!-- Scroll indicator -->
  <div class="scroll-indicator" aria-hidden="true">
    <span>SCROLL</span>
    <span class="scroll-line"></span>
  </div>
</header>

<!-- ============ ABOUT ============ -->
<section id="about">
  <div class="container about-grid">
    <div class="about-photo reveal-up">
      <img src="<?php echo c('about_photo'); ?>" alt="Profile picture"
        onerror="this.onerror=null;this.src='assets/images/about-placeholder.svg';">
    </div>
    <div class="about-text reveal-up">
      <span class="eyebrow">About Me</span>
      <h2 class="section-heading"><?php echo c('about_heading'); ?></h2>
      <p><?php echo nl2br(c('about_bio')); ?></p>

      <!-- Animated stat counters -->
      <div class="quick-stats">
        <div class="stat-card">
          <span class="stat-number" data-target="3" data-suffix="+">3+</span>
          <span class="stat-label">Projects</span>
        </div>
        <div class="stat-card">
          <span class="stat-number" data-target="4" data-suffix="">4</span>
          <span class="stat-label">Skill Areas</span>
        </div>
        <div class="stat-card">
          <span class="stat-number" data-target="1" data-suffix="yr">1yr</span>
          <span class="stat-label">Experience</span>
        </div>
      </div>

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
    <h2 class="section-heading">Where I've Been</h2>

    <div class="timeline-v2">
      <?php foreach ($timelineItems as $i => $item): ?>
        <?php $side = $i % 2 === 0 ? 'left' : 'right'; ?>
        <div class="timeline-entry timeline-entry--<?php echo $side; ?>">
          <div class="timeline-marker">
            <?php if ($item['icon_type'] === 'education'): ?>
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5" stroke-linecap="round"/></svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php endif; ?>
          </div>
          <article class="timeline-card">
            <div class="timeline-card-head">
              <div class="timeline-logo">
                <?php if ($item['icon_type'] === 'education'): ?>
                  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="var(--amber)" stroke-width="1.8"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5" stroke-linecap="round"/></svg>
                <?php else: ?>
                  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="var(--amber)" stroke-width="1.8"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?php endif; ?>
              </div>
              <div>
                <h3><?php echo e($item['title']); ?></h3>
                <p class="timeline-org"><?php echo e($item['organization']); ?></p>
              </div>
            </div>

            <div class="timeline-meta">
              <span>
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18" stroke-linecap="round"/></svg>
                <?php echo e($item['date_range']); ?>
              </span>
            </div>

            <details class="timeline-disclosure">
              <summary class="timeline-toggle">
                <span class="label-more">Show More</span>
                <span class="label-less">Show Less</span>
                <svg class="toggle-chevron" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </summary>
              <div class="timeline-details">
                <p><?php echo nl2br(e($item['description'])); ?></p>
              </div>
            </details>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ PROJECTS ============ -->
<!-- Project data source: <?php echo e($dataSource); ?> -->
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
        <p style="color:var(--ink-soft);">No projects found.</p>
      <?php else: ?>
        <?php foreach ($projects as $index => $project): ?>
          <article class="project-card" data-tags="<?php echo e(implode(',', $project['tags'])); ?>">
            <a href="https://youtube.com">
            <span class="project-spec">PROJECT_<?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
            <div class="project-thumb">
              <img src="<?php echo e($project['image']); ?>" alt="<?php echo e($project['title']); ?> screenshot" loading="lazy">
            </div>
            <div class="project-body">
              <h3><?php echo e($project['title']); ?></h3>
              <p><?php echo e($project['description']); ?></p>
              <div class="tag-list">
                <?php foreach ($project['tags'] as $tag): ?>
                  <span class="tag-chip"><?php echo e($tag); ?></span>
                <?php endforeach; ?>
              </div>
              <div class="project-links">
                <?php if (!empty($project['live'])): ?>
                  <a href="<?php echo e($project['live']); ?>" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Live Demo
                  </a>
                <?php endif; ?>
                <?php if (!empty($project['github'])): ?>
                  <a href="<?php echo e($project['github']); ?>" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M12 .5C5.37.5 0 5.87 0 12.5c0 5.31 3.435 9.818 8.205 11.405.6.11.82-.26.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.09-.745.083-.73.083-.73 1.205.085 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.604-2.665-.305-5.467-1.334-5.467-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.4 3-.405 1.02.005 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.312 24 17.807 24 12.5 24 5.87 18.627.5 12 .5z"/></svg>
                    Source Code
                  </a>
                <?php endif; ?>
                <?php if (!empty($project['presentation'])): ?>
                  <a href="<?php echo e($project['presentation']); ?>" download>
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 3v12M8 11l4 4 4-4M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Presentation
                  </a>
                <?php endif; ?>
              </div>
            </div>
  
            </a>
            
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
                $skillName = is_array($skill) ? $skill['name'] : $skill;
                $skillIcon = is_array($skill) ? ($skill['icon'] ?? '') : '';
              ?>
              <li>
                <?php if (!empty($skillIcon)): ?>
                  <img src="<?php echo e($skillIcon); ?>" alt="" class="skill-icon" loading="lazy"
                    onerror="this.style.display='none';">
                <?php else: ?>
                  <span style="width:22px;height:22px;border-radius:4px;background:var(--amber-soft);display:inline-flex;align-items:center;justify-content:center;font-size:0.65rem;color:var(--amber);flex-shrink:0;">●</span>
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
      <h2 class="section-heading">Let's Work<br>Together</h2>
      <p><?php echo c('contact_intro'); ?></p>
      <ul class="contact-methods">
        <li>
          <span class="contact-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg>
          </span>
          <span class="label">Email</span>
          <a href="mailto:<?php echo c('contact_email'); ?>"><?php echo c('contact_email'); ?></a>
        </li>
        <li>
          <span class="contact-icon">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.37.5 0 5.87 0 12.5c0 5.31 3.435 9.818 8.205 11.405.6.11.82-.26.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.09-.745.083-.73.083-.73 1.205.085 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.604-2.665-.305-5.467-1.334-5.467-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.4 3-.405 1.02.005 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.312 24 17.807 24 12.5 24 5.87 18.627.5 12 .5z"/></svg>
          </span>
          <span class="label">GitHub</span>
          <a href="<?php echo c('contact_github'); ?>" target="_blank" rel="noopener">Pemindu-Prabodhitha</a>
        </li>
        <li>
          <span class="contact-icon">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
          </span>
          <span class="label">LinkedIn</span>
          <a href="<?php echo c('contact_linkedin'); ?>" target="_blank" rel="noopener">pemindu-prabodhitha</a>
        </li>
        <li>
          <span class="contact-icon">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </span>
          <span class="label">WhatsApp</span>
          <a href="https://wa.me/<?php echo e(ltrim($siteContent['contact_whatsapp'], '+')); ?>" target="_blank" rel="noopener"><?php echo c('contact_whatsapp'); ?></a>
        </li>
      </ul>
    </div>

    <form class="contact-form reveal-up" id="contactForm" novalidate>
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required autocomplete="name" placeholder="Your full name">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="email" placeholder="your@email.com">
      </div>
      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="5" required placeholder="Tell me about your project…"></textarea>
      </div>

      <!-- Honeypot field -->
      <div class="form-group hp-field" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-status" id="formStatus" role="status" aria-live="polite"></div>

      <button type="submit" class="btn btn-primary submit-btn" id="submitBtn">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Send Message
      </button>
    </form>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer>
  <div class="footer-inner">
    <div class="footer-logo"><?php echo c('nav_logo_first'); ?><span>.</span><?php echo c('nav_logo_last'); ?></div>

    <ul class="footer-socials">
      <li>
        <a href="<?php echo c('contact_github'); ?>" target="_blank" rel="noopener" class="footer-social-link" aria-label="GitHub">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.37.5 0 5.87 0 12.5c0 5.31 3.435 9.818 8.205 11.405.6.11.82-.26.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.09-.745.083-.73.083-.73 1.205.085 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.604-2.665-.305-5.467-1.334-5.467-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.4 3-.405 1.02.005 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.312 24 17.807 24 12.5 24 5.87 18.627.5 12 .5z"/></svg>
        </a>
      </li>
      <li>
        <a href="<?php echo c('contact_linkedin'); ?>" target="_blank" rel="noopener" class="footer-social-link" aria-label="LinkedIn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        </a>
      </li>
      <li>
        <a href="https://wa.me/94704282988" target="_blank" rel="noopener" class="footer-social-link" aria-label="WhatsApp">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
      </li>
      <li>
        <a href="https://www.facebook.com/share/198gf97GNH/?mibextid=wwXIfr" target="_blank" rel="noopener" class="footer-social-link" aria-label="Facebook">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        </a>
      </li>
      <li>
        <a href="https://www.instagram.com/_pemindu_?igsh=Z3RnMWxxc24wMHdh&utm_source=qr" target="_blank" rel="noopener" class="footer-social-link" aria-label="Instagram">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
        </a>
      </li>
    </ul>

    <p class="footer-copy">&copy; <?php echo date('Y'); ?> <?php echo c('footer_name'); ?>. All Rights Reserved.</p>
  </div>
</footer>

<button class="back-to-top" id="backToTop" aria-label="Back to top">
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V5M5 12l7-7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>

<script src="js/main.js"></script>
</body>
</html>
