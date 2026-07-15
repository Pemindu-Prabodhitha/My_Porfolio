<?php
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db_connection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $id !== null;

$project = [
    'title' => '',
    'description' => '',
    'tags' => '',
    'image' => '',
    'live_url' => '',
    'github_url' => '',
    'presentation_path' => '',
    'video_path' => '',
    'sort_order' => 0,
    'video_url' => '',
];

$errors = [];

if ($pdo === null) {
    $errors[] = 'Database is not connected. Check php/config.php.';
}

// Load existing project when editing
if ($isEdit && $pdo !== null) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $project = $existing;
        } else {
            $errors[] = 'Project not found.';
            $isEdit = false;
            $id = null;
        }
    } catch (PDOException $e) {
        $errors[] = 'Could not load project.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $errors[] = 'The uploaded file is too large. Please upload a smaller video or increase your server limits.';
    } elseif (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $project['title'] = trim($_POST['title'] ?? '');
        $project['description'] = trim($_POST['description'] ?? '');
        $project['tags'] = trim($_POST['tags'] ?? '');
        $project['image'] = trim($_POST['image'] ?? '');
        $project['live_url'] = trim($_POST['live_url'] ?? '');
        $project['github_url'] = trim($_POST['github_url'] ?? '');
        $project['presentation_path'] = trim($_POST['presentation_path'] ?? '');
        $project['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
        $project['video_url'] = trim($_POST['video_url'] ?? '');

        if ($project['title'] === '' || strlen($project['title']) > 150) {
            $errors[] = 'Title is required (max 150 characters).';
        }
        if ($project['description'] === '') {
            $errors[] = 'Description is required.';
        }
        if ($project['tags'] === '') {
            $errors[] = 'Add at least one tag (comma-separated, e.g. "PHP,MySQL").';
        }
        if ($project['image'] === '') {
            $errors[] = 'Image path is required (e.g. assets/images/my-project.jpg).';
        }
        foreach (['live_url', 'github_url','video_url'] as $urlField) {
            if ($project[$urlField] !== '' && !filter_var($project[$urlField], FILTER_VALIDATE_URL)) {
                $errors[] = ucfirst(str_replace('_', ' ', $urlField)) . ' must be a valid URL.';
            }
        }

        // Handle video file upload
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $videoTmpPath = $_FILES['video_file']['tmp_name'];
            $videoName = basename($_FILES['video_file']['name']);
            // Generate a unique filename
            $uniqueVideoName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $videoName);
            $uploadDir = __DIR__ . '/../../assets/videos/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $destination = $uploadDir . $uniqueVideoName;
            if (move_uploaded_file($videoTmpPath, $destination)) {
                $project['video_path'] = 'assets/videos/' . $uniqueVideoName;
            } else {
                $errors[] = 'Failed to upload video file.';
            }
        } else {
            // keep the old video path if editing and no new file was uploaded
            if ($isEdit && empty($errors)) {
                $stmt = $pdo->prepare('SELECT video_path FROM projects WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $oldData = $stmt->fetch();
                if ($oldData && !empty($oldData['video_path'])) {
                    $project['video_path'] = $oldData['video_path'];
                }
            } else {
                $project['video_path'] = '';
            }
        }

        if (empty($errors) && $pdo !== null) {
            try {
                if ($isEdit) {
                    $stmt = $pdo->prepare(
                        'UPDATE projects SET title=:title, description=:description, tags=:tags,
                         image=:image, live_url=:live_url, github_url=:github_url,
                         presentation_path=:presentation_path, video_path=:video_path, sort_order=:sort_order,video_url=:video_url
                         WHERE id=:id'
                    );
                    $stmt->execute([
                        ':title' => $project['title'],
                        ':description' => $project['description'],
                        ':tags' => $project['tags'],
                        ':image' => $project['image'],
                        ':live_url' => $project['live_url'] ?: null,
                        ':github_url' => $project['github_url'] ?: null,
                        ':presentation_path' => $project['presentation_path'] ?: null,
                        ':video_path' => $project['video_path'] ?: null,
                        ':sort_order' => $project['sort_order'],
                        ':id' => $id,
                        ':video_url' => $project['video_url'] ?: null,
                    ]);
                    header('Location: projects.php?flash=updated');
                    exit;
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO projects (title, description, tags, image, live_url, github_url, presentation_path, video_path, sort_order,video_url)
                         VALUES (:title, :description, :tags, :image, :live_url, :github_url, :presentation_path, :video_path, :sort_order,:video_url)'
                    );
                    $stmt->execute([
                        ':title' => $project['title'],
                        ':description' => $project['description'],
                        ':tags' => $project['tags'],
                        ':image' => $project['image'],
                        ':live_url' => $project['live_url'] ?: null,
                        ':github_url' => $project['github_url'] ?: null,
                        ':presentation_path' => $project['presentation_path'] ?: null,
                        ':video_path' => $project['video_path'] ?: null,
                        ':sort_order' => $project['sort_order'],
                        ':video_url' => $project['video_url'] ?: null,
                    ]);
                    header('Location: projects.php?flash=created');
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = 'Could not save project. Please try again.';
                error_log('Project save failed: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = $isEdit ? 'Edit Project' : 'Add Project';
$activeNav = 'projects';
include __DIR__ . '/_header.php';
?>

<div class="admin-heading">
  <h1><?php echo $isEdit ? 'Edit Project' : 'Add New Project'; ?></h1>
  <a href="projects.php" class="btn btn-outline btn-sm">← Back to Projects</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="flash error"><?php echo e($err); ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

  <div class="form-group">
    <label for="title">Title</label>
    <input type="text" id="title" name="title" value="<?php echo e($project['title']); ?>" required>
  </div>

  <div class="form-group">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4" required><?php echo e($project['description']); ?></textarea>
  </div>

  <div class="form-group">
    <label for="tags">Tags</label>
    <input type="text" id="tags" name="tags" value="<?php echo e($project['tags']); ?>" placeholder="PHP,MySQL,JavaScript" required>
    <p class="hint">Comma-separated. These populate the filter buttons on the site.</p>
  </div>

  <div class="form-group">
    <label for="image">Image Path</label>
    <input type="text" id="image" name="image" value="<?php echo e($project['image']); ?>" placeholder="assets/images/my-project.jpg" required>
    <p class="hint">Path relative to the site root. Upload the actual image file into assets/images/ separately.</p>
  </div>

  <div class="form-group">
    <label for="live_url">Live Demo URL (optional)</label>
    <input type="url" id="live_url" name="live_url" value="<?php echo e($project['live_url']); ?>" placeholder="https://example.com">
  </div>

  <div class="form-group">
    <label for="github_url">GitHub URL (optional)</label>
    <input type="url" id="github_url" name="github_url" value="<?php echo e($project['github_url']); ?>" placeholder="https://github.com/you/project">
  </div>

  <div class="form-group">
    <label for="video_url">Video URL (optional)</label>
    <input type="url" id="video_url" name="video_url" value="<?php echo e($project['video_url']); ?>" placeholder="https://video.com/you/project">
  </div>

  <div class="form-group">
    <label for="presentation_path">Presentation File Path (optional)</label>
    <input type="text" id="presentation_path" name="presentation_path" value="<?php echo e($project['presentation_path'] ?? ''); ?>" placeholder="assets/presentations/my-project.pdf">
    <p class="hint">Path relative to the site root. Upload the actual file (PDF/PPTX) into an assets folder separately — this just adds a "Download Presentation" button to the project card.</p>
  </div>

  <div class="form-group">
    <label for="video_file">Project Video File (optional)</label>
    <input type="file" id="video_file" name="video_file" accept="video/*">
    <p class="hint">Upload an MP4 or similar video. This makes the project card clickable to download the video on the homepage.</p>
    <?php if (!empty($project['video_path'])): ?>
      <p class="hint">Current video: <a href="../../<?php echo e($project['video_path']); ?>" target="_blank" style="color:var(--amber);text-decoration:underline;"><?php echo e(basename($project['video_path'])); ?></a></p>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label for="sort_order">Sort Order</label>
    <input type="number" id="sort_order" name="sort_order" value="<?php echo e($project['sort_order']); ?>">
    <p class="hint">Lower numbers appear first on the site.</p>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Save Changes' : 'Create Project'; ?></button>
    <a href="projects.php" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/_footer.php'; ?>
