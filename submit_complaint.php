<?php
/**
 * ComplaintBox — Submit Complaint
 * Creates a new complaint in MySQL. Supports anonymous submission.
 */
declare(strict_types=1);

$active = 'submit';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$old = ['title' => '', 'category' => '', 'description' => '', 'anonymous' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = clean($_POST['title'] ?? '');
    $category    = clean($_POST['category'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $anonymous   = isset($_POST['anonymous']) ? 1 : 0;

    $old = ['title' => $title, 'category' => $category, 'description' => $description, 'anonymous' => $anonymous];

    /* ---------- Evidence upload ---------- */
    $evidencePath = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['evidence'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($file['size'] > $maxSize) {
            $errors['evidence'] = 'File size must be 5MB or less.';
        } else {
            // Detect the REAL file type from its contents. The browser-supplied
            // $_FILES['type'] is untrusted and often wrong (e.g. image/jpg,
            // application/octet-stream) for files downloaded from the web.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = (string) $finfo->file($file['tmp_name']);

            $aliases = [
                'image/jpg'       => 'image/jpeg',
                'image/jfif'      => 'image/jpeg',
                'image/pjpeg'     => 'image/jpeg',
                'image/x-png'     => 'image/png',
                'image/x-ms-bmp'  => 'image/bmp',
            ];
            $mime = $aliases[$mime] ?? $mime;

            $extByMime = [
                'image/jpeg'        => 'jpg',
                'image/png'         => 'png',
                'image/gif'         => 'gif',
                'image/webp'        => 'webp',
                'image/avif'        => 'avif',
                'image/bmp'         => 'bmp',
                'application/pdf'   => 'pdf',
            ];

            if (!isset($extByMime[$mime])) {
                $errors['evidence'] = 'Only image (JPG, PNG, GIF, WebP, AVIF, BMP) or PDF files are allowed.';
            } else {
                $uploadDir = __DIR__ . '/uploads/complaints';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                // Use an extension that matches the real contents, no matter what
                // the original filename was (e.g. .webp/.jfif from Google/Windows).
                $evidencePath = 'uploads/complaints/' . uniqid('ev_', true) . '.' . $extByMime[$mime];
                if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $evidencePath)) {
                    $errors['evidence'] = 'Failed to upload file. Please try again.';
                    $evidencePath = null;
                }
            }
        }
    }

    /* ---------- Validation ---------- */
    if ($title === '') {
        $errors['title'] = 'This field is required.';
    } elseif (strlen($title) > 160) {
        $errors['title'] = 'Title must be 160 characters or fewer.';
    }

    if ($category === '') {
        $errors['category'] = 'Please select a category.';
    } elseif (!array_key_exists($category, complaint_categories())) {
        $errors['category'] = 'Please select a valid category.';
    }

    if ($description === '') {
        $errors['description'] = 'This field is required.';
    } elseif (strlen($description) < 10) {
        $errors['description'] = 'Description must be at least 10 characters.';
    } elseif (strlen($description) > 2000) {
        $errors['description'] = 'Description must be 2000 characters or fewer.';
    }

    /* ---------- Insert ---------- */
    if (empty($errors)) {
        $complaintId = generate_complaint_id();

        $stmt = db()->prepare(
            "INSERT INTO complaints (complaint_id, user_id, title, description, category_id, status_id, anonymous, evidence)
             VALUES (:cid, :uid, :title, :desc, :cat_id, :status_id, :anon, :evidence)"
        );
        $stmt->execute([
            ':cid'       => $complaintId,
            ':uid'       => (int) $current_user['id'],
            ':title'     => $title,
            ':desc'      => $description,
            ':cat_id'    => category_id_by_name($category),
            ':status_id' => status_id_by_name('pending'),
            ':anon'      => $anonymous,
            ':evidence'  => $evidencePath,
        ]);

        set_flash('success', 'Complaint submitted successfully. Your Complaint ID is ' . $complaintId);
        redirect('my_complaints.php');
    }
}

$page_title = 'Submit Complaint';
include __DIR__ . '/includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main">
    <div class="card">
      <div class="card-head">
        <div>
          <h2>Submit New Complaint</h2>
          <p>Fill in the details below — we'll take it from there.</p>
        </div>
      </div>

      <form action="submit_complaint.php" method="POST" data-validate-form novalidate enctype="multipart/form-data" style="margin-top:24px;">
        <div class="form-group <?php echo isset($errors['title']) ? 'invalid' : ''; ?>" data-validate="required">
          <label for="title">Complaint Title</label>
          <input type="text" id="title" name="title" value="<?php echo e($old['title']); ?>" placeholder="e.g. Broken projector in Room 204" />
          <span class="field-error"><?php echo e($errors['title'] ?? 'This field is required.'); ?></span>
        </div>

        <div class="form-group <?php echo isset($errors['category']) ? 'invalid' : ''; ?>" data-validate="required">
          <label for="category">Category</label>
          <select id="category" name="category">
            <option value="" disabled <?php echo $old['category'] === '' ? 'selected' : ''; ?>>Select category</option>
            <?php foreach (complaint_categories() as $key => $label): ?>
              <option value="<?php echo e($key); ?>" <?php echo $old['category'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
          </select>
          <span class="field-error">Please select a category.</span>
        </div>

        <div class="form-group <?php echo isset($errors['description']) ? 'invalid' : ''; ?>" data-validate="required">
          <label for="description">Detailed Description</label>
          <textarea id="description" name="description" rows="6" placeholder="Describe your concern in detail..."><?php echo e($old['description']); ?></textarea>
          <span class="field-error"><?php echo e($errors['description'] ?? 'This field is required.'); ?></span>
        </div>

        <div class="form-group <?php echo isset($errors['evidence']) ? 'invalid' : ''; ?>">
          <label for="evidence">Upload Evidence (optional)</label>
          <input type="file" id="evidence" name="evidence" style="height:auto;padding:12px 16px;" />
          <span class="field-error"><?php echo e($errors['evidence'] ?? 'Only image (JPG, PNG, GIF, WebP, AVIF, BMP) or PDF files are allowed (max 5MB).'); ?></span>
          <small style="color:var(--muted);font-size:0.78rem;margin-top:4px;display:block;">Attach a photo or document as evidence. Photos downloaded from Google (WebP/JFIF) are supported. Max 5MB.</small>
        </div>

        <label class="checkbox-group" for="anonymous">
          <input type="checkbox" id="anonymous" name="anonymous" <?php echo $old['anonymous'] ? 'checked' : ''; ?> />
          <span>
            <strong>Submit anonymously</strong> — your identity will be hidden from the institution team.
            You can still track this complaint.
          </span>
        </label>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-paper-plane"></i> Submit Complaint
          </button>
          <a href="dashboard.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
