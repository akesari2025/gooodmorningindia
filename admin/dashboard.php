<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = get_db();
$message = '';
$messageType = '';
$uploadDir = __DIR__ . '/../uploads';
const MAX_IMAGE_SIZE = 10 * 1024; // 10KB
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Ensure uploads directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle add form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $usernameRaw = trim($_POST['username'] ?? '');
    $username = strtolower(ltrim($usernameRaw, '@'));
    $name = trim($_POST['name'] ?? '');
    $imagePath = null;

    if (empty($username)) {
        $message = 'Please enter a username.';
        $messageType = 'error';
    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            if ($file['size'] > MAX_IMAGE_SIZE) {
                $message = 'Image must be 10KB or smaller.';
                $messageType = 'error';
            } elseif (!in_array($file['type'], ALLOWED_TYPES)) {
                $message = 'Invalid image type. Use JPG, PNG, GIF, or WebP.';
                $messageType = 'error';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $filename = uniqid('img_', true) . '.' . $ext;
                $target = $uploadDir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $imagePath = 'uploads/' . $filename;
                } else {
                    $message = 'Failed to save image.';
                    $messageType = 'error';
                }
            }
        }

        if ($messageType !== 'error') {
            try {
                $stmt = $pdo->prepare("SELECT id FROM followers WHERE LOWER(TRIM(BOTH '@' FROM username)) = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $message = 'This username already exists. User IDs must be unique.';
                    $messageType = 'error';
                } else {
                $stmt = $pdo->prepare("INSERT INTO followers (username, name, image) VALUES (?, ?, ?)");
                $stmt->execute([$username, $name ?: null, $imagePath]);
                $message = 'User "@' . htmlspecialchars($username) . '" saved successfully.';
                $messageType = 'success';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = 'This username already exists. User IDs must be unique.';
                } else {
                    $message = 'Failed to save. Please try again.';
                }
                $messageType = 'error';
            }
        }
    }
}

// Fetch recent followers
$recent = [];
try {
    $cols = ['id', 'username', 'name', 'image', 'created_at'];
    $stmt = $pdo->query("SELECT " . implode(', ', $cols) . " FROM followers ORDER BY created_at DESC LIMIT 100");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback for old schema
    try {
        $stmt = $pdo->query("SELECT id, instagram_id as username, created_at FROM followers ORDER BY created_at DESC LIMIT 100");
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $message = 'Could not load followers.';
        $messageType = 'error';
    }
}

$total = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM followers");
    $total = (int) $stmt->fetchColumn();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Good Morning India</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body { background: linear-gradient(135deg, #f0f4ff 0%, #e8eef8 100%); min-height: 100vh; }
        .admin-wrap { max-width: 720px; margin: 0 auto; padding: 32px 24px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; padding: 16px 0; flex-wrap: wrap; gap: 16px; }
        .admin-header h1 { color: #1f41a8; font-size: 1.75rem; font-weight: 700; margin: 0; letter-spacing: -0.02em; }
        .admin-actions { display: flex; gap: 12px; }
        .admin-actions a { display: inline-flex; align-items: center; padding: 8px 16px; color: #1f41a8; text-decoration: none; font-weight: 500; font-size: 14px; border-radius: 8px; background: rgba(31,65,168,0.08); transition: background 0.2s; }
        .admin-actions a:hover { background: rgba(31,65,168,0.15); }
        .card { background: #fff; border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 24px rgba(31,65,168,0.06), 0 1px 3px rgba(0,0,0,0.04); border: 1px solid rgba(31,65,168,0.06); }
        .card h2 { margin: 0 0 20px; font-size: 1.125rem; font-weight: 600; color: #1a1a2e; }
        .add-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
        @media (max-width: 560px) { .add-form { grid-template-columns: 1fr; } }
        .form-group { margin: 0; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #374151; }
        .form-group input[type="text"] { width: 100%; padding: 12px 16px; font-size: 15px; border: 1.5px solid #e5e7eb; border-radius: 10px; background: #fafafa; transition: border-color 0.2s, background 0.2s; }
        .form-group input[type="text"]:focus { outline: none; border-color: #1f41a8; background: #fff; box-shadow: 0 0 0 3px rgba(31,65,168,0.1); }
        .form-group input[type="text"]::placeholder { color: #9ca3af; }
        .file-wrap { display: flex; flex-direction: column; gap: 6px; }
        .file-wrap input[type="file"] { padding: 10px 12px; font-size: 14px; border: 1.5px dashed #d1d5db; border-radius: 10px; background: #f9fafb; cursor: pointer; }
        .file-wrap input[type="file"]:hover { border-color: #1f41a8; background: #f0f4ff; }
        .form-hint { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .form-actions { grid-column: 1 / -1; margin-top: 4px; }
        .btn { padding: 12px 28px; font-size: 15px; font-weight: 600; color: #fff; background: linear-gradient(135deg, #1f41a8 0%, #163580 100%); border: none; border-radius: 10px; cursor: pointer; box-shadow: 0 2px 8px rgba(31,65,168,0.25); transition: transform 0.15s, box-shadow 0.2s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(31,65,168,0.3); }
        .message { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; }
        .message.success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .message.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .stats { font-size: 14px; color: #6b7280; margin-bottom: 20px; padding: 8px 0; }
        .stats strong { color: #1f41a8; }
        .follower-list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 12px 16px; background: #f8fafc; border-radius: 10px; gap: 12px; flex-wrap: wrap; }
        .follower-list-header label { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; color: #374151; cursor: pointer; }
        .follower-list-header input[type="checkbox"] { width: 18px; height: 18px; accent-color: #1f41a8; cursor: pointer; }
        .btn-delete { padding: 8px 18px; font-size: 13px; font-weight: 600; background: #dc2626; color: #fff; border: none; border-radius: 8px; cursor: pointer; transition: background 0.2s; }
        .btn-delete:hover { background: #b91c1c; }
        .follower-list { max-height: 380px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: #fafbfc; }
        .follower-item { display: flex; align-items: center; padding: 12px 16px; font-size: 14px; gap: 14px; border-bottom: 1px solid #eee; transition: background 0.15s; }
        .follower-item:last-child { border-bottom: none; }
        .follower-item:hover { background: #fff; }
        .follower-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: #1f41a8; cursor: pointer; flex-shrink: 0; }
        .follower-item img, .follower-item .avatar-placeholder { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
        .avatar-placeholder { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: #4f46e5; }
        .follower-info { flex: 1; min-width: 0; }
        .follower-username { font-weight: 600; color: #1f41a8; }
        .follower-name { font-size: 13px; color: #6b7280; margin-left: 4px; }
        .follower-date { color: #9ca3af; font-size: 12px; flex-shrink: 0; }
        .follower-actions { flex-shrink: 0; }
        .btn-delete-small { padding: 6px 12px; font-size: 12px; font-weight: 500; background: transparent; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
        .btn-delete-small:hover { background: #fee2e2; border-color: #f87171; }
    </style>
</head>
<body>
    <div class="admin-wrap">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div class="admin-actions">
                <a href="manage-followers.php">Manage Followers</a>
                <a href="pick-winner.php">Pick Random Winner</a>
                <a href="../winners.php">Winners</a>
                <a href="../index.php">View Site</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Add Follower</h2>
            <p class="stats">Total followers: <strong><?= $total ?></strong></p>
            <form method="post" action="" enctype="multipart/form-data" class="add-form" id="add-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="e.g. johndoe" required>
                </div>
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Full name (optional)">
                </div>
                <div class="form-group full-width">
                    <label for="image">Image</label>
                    <div class="file-wrap">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <span class="form-hint">Max 10KB. JPG, PNG, GIF, or WebP.</span>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Add Follower</button>
                </div>
            </form>
            <script>
                document.getElementById('add-form').addEventListener('submit', function(e) {
                    var file = document.getElementById('image');
                    if (file.files.length && file.files[0].size > 10240) {
                        e.preventDefault();
                        alert('Image must be 10KB or smaller.');
                    }
                });
            </script>
        </div>

        <div class="card">
            <h2>Recent Followers (last 100)</h2>
            <?php if (empty($recent)): ?>
                <p style="color:#888; padding:16px;">No followers added yet.</p>
            <?php else: ?>
                <div class="follower-list">
                    <?php foreach ($recent as $row): ?>
                        <div class="follower-item">
                            <?php if (!empty($row['image'])): ?>
                                <img src="../<?= htmlspecialchars($row['image']) ?>" alt="">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?= strtoupper(mb_substr($row['username'] ?? '', 0, 1)) ?: '?' ?></div>
                            <?php endif; ?>
                            <div class="follower-info">
                                <span class="follower-username"><?= htmlspecialchars($row['username']) ?></span>
                                <?php if (!empty($row['name'])): ?>
                                    <span class="follower-name"> — <?= htmlspecialchars($row['name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="follower-date"><?= date('M j, Y', strtotime($row['created_at'] ?? 'now')) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
