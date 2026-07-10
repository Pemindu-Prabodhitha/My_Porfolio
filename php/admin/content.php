<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$errors = [];
$success = false;

// Every editable key, and which ones are allowed a small set of safe inline
// tags (br/span/strong/em) instead of being stripped to plain text.
$fieldKeys = [
    'site_title', 'site_description',
    'nav_logo_first', 'nav_logo_last',
    'hero_tag', 'hero_heading', 'hero_lead', 'hero_image', 'resume_path',
    'about_heading', 'about_bio', 'about_photo', 'about_location', 'about_focus', 'about_currently',
    'contact_intro', 'contact_email', 'contact_github', 'contact_linkedin', 'contact_whatsapp',
    'footer_name',
];
$richTextKeys = ['hero_heading', 'hero_lead'];

$values = [];

if ($pdo === null) {
    $errors[] = 'Database is not connected. Check php/config.php.';
} else {
    try {
        $rows = $pdo->query('SELECT content_key, content_value FROM site_content')->fetchAll();
        foreach ($rows as $row) {
            $values[$row['content_key']] = $row['content_value'];
        }
    } catch (PDOException $e) {
        $errors[] = 'Could not load site content. Has database/schema.sql been imported?';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO site_content (content_key, content_value) VALUES (:k, :v)
                 ON DUPLICATE KEY UPDATE content_value = :v2'
            );

            foreach ($fieldKeys as $key) {
                $raw = trim($_POST[$key] ?? '');

                // hero_heading / hero_lead may contain a few safe inline tags;
                // everything else is stripped down to plain text.
                $clean = in_array($key, $richTextKeys, true)
                    ? strip_tags($raw, '<br><span><strong><em>')
                    : strip_tags($raw);

                $stmt->execute([':k' => $key, ':v' => $clean, ':v2' => $clean]);
                $values[$key] = $clean;
            }

            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Could not save changes. Please try again.';
            error_log('Site content save failed: ' . $e->getMessage());
        }
    }
}

// Small helper for filling form fields safely
function v($values, $key) {
    return htmlspecialchars($values[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Site Content';
$activeNav = 'content';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1>Site Content</h1>
</div>

<?php if ($success): ?>
  <div class="flash success">Changes saved. <a href="../../index.php" target="_blank" style="text-decoration:underline;">View the live site</a> to see them.</div>
<?php endif; ?>

<?php foreach ($errors as $err): ?>
  <div class="flash error"><?php echo e($err); ?></div>
<?php endforeach; ?>

<?php if ($pdo !== null): ?>
<form method="post" class="admin-form" style="max-width:800px;">
  <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

  <h3 style="font-family:var(--font-mono);font-size:0.8rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--blueprint);margin-bottom:1rem;">General</h3>

  <div class="form-group">
    <label for="site_title">Browser Tab Title</label>
    <input type="text" id="site_title" name="site_title" value="<?php echo v($values, 'site_title'); ?>">
  </div>

  <div class="form-group">
    <label for="site_description">Meta Description (for search engines)</label>
    <input type="text" id="site_description" name="site_description" value="<?php echo v($values, 'site_description'); ?>">
  </div>

  <div class="form-group">
    <label for="nav_logo_first">Nav Logo — First Part</label>
    <input type="text" id="nav_logo_first" name="nav_logo_first" value="<?php echo v($values, 'nav_logo_first'); ?>">
  </div>

  <div class="form-group">
    <label for="nav_logo_last">Nav Logo — Second Part</label>
    <input type="text" id="nav_logo_last" name="nav_logo_last" value="<?php echo v($values, 'nav_logo_last'); ?>">
    <p class="hint">Displayed as FIRST.SECOND in the top navigation.</p>
  </div>

  <hr style="border:none;border-top:1px solid var(--line);margin:2rem 0;">
  <h3 style="font-family:var(--font-mono);font-size:0.8rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--blueprint);margin-bottom:1rem;">Hero Section</h3>

  <div class="form-group">
    <label for="hero_tag">Small Tag Line</label>
    <input type="text" id="hero_tag" name="hero_tag" value="<?php echo v($values, 'hero_tag'); ?>">
  </div>

  <div class="form-group">
    <label for="hero_heading">Main Heading</label>
    <textarea id="hero_heading" name="hero_heading" rows="3"><?php echo v($values, 'hero_heading'); ?></textarea>
    <p class="hint">Allowed tags: &lt;br&gt; for line breaks, &lt;span class="accent"&gt; to highlight a word in amber, &lt;strong&gt;/&lt;em&gt;.</p>
  </div>

  <div class="form-group">
    <label for="hero_lead">Intro Paragraph</label>
    <textarea id="hero_lead" name="hero_lead" rows="3"><?php echo v($values, 'hero_lead'); ?></textarea>
    <p class="hint">Same allowed tags as above — use &lt;strong&gt; to bold your name.</p>
  </div>

  <div class="form-group">
    <label for="hero_image">Hero Photo Path</label>
    <input type="text" id="hero_image" name="hero_image" value="<?php echo v($values, 'hero_image'); ?>">
    <p class="hint">Path relative to the site root, e.g. assets/images/hero.jpg. Upload the file into assets/images/ separately. Use .jpg/.png/.webp — .heic files won't display in most browsers.</p>
  </div>

  <div class="form-group">
    <label for="resume_path">Résumé File Path</label>
    <input type="text" id="resume_path" name="resume_path" value="<?php echo v($values, 'resume_path'); ?>">
    <p class="hint">Tip: file names with spaces can cause issues on some servers — consider renaming to use hyphens instead (e.g. resume.pdf).</p>
  </div>

  <hr style="border:none;border-top:1px solid var(--line);margin:2rem 0;">
  <h3 style="font-family:var(--font-mono);font-size:0.8rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--blueprint);margin-bottom:1rem;">About Section</h3>

  <div class="form-group">
    <label for="about_heading">Section Heading</label>
    <input type="text" id="about_heading" name="about_heading" value="<?php echo v($values, 'about_heading'); ?>">
  </div>

  <div class="form-group">
    <label for="about_bio">Bio Paragraph</label>
    <textarea id="about_bio" name="about_bio" rows="6"><?php echo v($values, 'about_bio'); ?></textarea>
  </div>

  <div class="form-group">
    <label for="about_photo">About Photo Path</label>
    <input type="text" id="about_photo" name="about_photo" value="<?php echo v($values, 'about_photo'); ?>">
  </div>

  <div class="form-group">
    <label for="about_location">Location</label>
    <input type="text" id="about_location" name="about_location" value="<?php echo v($values, 'about_location'); ?>">
  </div>

  <div class="form-group">
    <label for="about_focus">Focus</label>
    <input type="text" id="about_focus" name="about_focus" value="<?php echo v($values, 'about_focus'); ?>">
  </div>

  <div class="form-group">
    <label for="about_currently">Currently</label>
    <input type="text" id="about_currently" name="about_currently" value="<?php echo v($values, 'about_currently'); ?>">
  </div>

  <hr style="border:none;border-top:1px solid var(--line);margin:2rem 0;">
  <h3 style="font-family:var(--font-mono);font-size:0.8rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--blueprint);margin-bottom:1rem;">Contact Section</h3>

  <div class="form-group">
    <label for="contact_intro">Intro Paragraph</label>
    <textarea id="contact_intro" name="contact_intro" rows="3"><?php echo v($values, 'contact_intro'); ?></textarea>
  </div>

  <div class="form-group">
    <label for="contact_email">Email</label>
    <input type="text" id="contact_email" name="contact_email" value="<?php echo v($values, 'contact_email'); ?>">
  </div>

  <div class="form-group">
    <label for="contact_github">GitHub URL</label>
    <input type="text" id="contact_github" name="contact_github" value="<?php echo v($values, 'contact_github'); ?>">
  </div>

  <div class="form-group">
    <label for="contact_linkedin">LinkedIn URL</label>
    <input type="text" id="contact_linkedin" name="contact_linkedin" value="<?php echo v($values, 'contact_linkedin'); ?>">
  </div>

  <div class="form-group">
    <label for="contact_whatsapp">WhatsApp Number</label>
    <input type="text" id="contact_whatsapp" name="contact_whatsapp" value="<?php echo v($values, 'contact_whatsapp'); ?>" placeholder="+94704282988">
    <p class="hint">Include the country code with a leading +. Used to build the wa.me link automatically.</p>
  </div>

  <hr style="border:none;border-top:1px solid var(--line);margin:2rem 0;">
  <h3 style="font-family:var(--font-mono);font-size:0.8rem;text-transform:uppercase;letter-spacing:0.06em;color:var(--blueprint);margin-bottom:1rem;">Footer</h3>

  <div class="form-group">
    <label for="footer_name">Footer Name</label>
    <input type="text" id="footer_name" name="footer_name" value="<?php echo v($values, 'footer_name'); ?>">
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Save Changes</button>
  </div>
</form>
<?php endif; ?>

<?php include __DIR__ . '/_footer.php'; ?>
