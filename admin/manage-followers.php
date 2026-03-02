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

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = null;

// Handle delete (single or bulk)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['delete_single']) || isset($_POST['delete_ids']))) {
    $ids = [];
    if (isset($_POST['delete_single']) && is_numeric($_POST['delete_single'])) {
        $ids = [(int) $_POST['delete_single']];
    } elseif (!empty($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
        $ids = array_filter(array_map('intval', $_POST['delete_ids']));
    }
    if (!empty($ids)) {
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM followers WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $n = $stmt->rowCount();
            $message = $n === 1 ? '1 follower deleted.' : $n . ' followers deleted.';
            $messageType = 'success';
            $editId = null;
        } catch (PDOException $e) {
            $message = 'Failed to delete.';
            $messageType = 'error';
        }
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id']) && is_numeric($_POST['edit_id'])) {
    $id = (int) $_POST['edit_id'];
    $usernameRaw = trim($_POST['username'] ?? '');
    $username = strtolower(ltrim($usernameRaw, '@'));
    $name = trim($_POST['name'] ?? '');
    $imagePath = null;

    if (empty($username)) {
        $message = 'Please enter a username.';
        $messageType = 'error';
        $editId = $id;
    } else {
        // Fetch current row to keep image if not uploading new one
        $stmt = $pdo->prepare("SELECT image FROM followers WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        $imagePath = $current['image'] ?? null;

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            if ($file['size'] > MAX_IMAGE_SIZE) {
                $message = 'Image must be 10KB or smaller.';
                $messageType = 'error';
                $editId = $id;
            } elseif (!in_array($file['type'], ALLOWED_TYPES)) {
                $message = 'Invalid image type. Use JPG, PNG, GIF, or WebP.';
                $messageType = 'error';
                $editId = $id;
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $filename = uniqid('img_', true) . '.' . $ext;
                $target = $uploadDir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $imagePath = 'uploads/' . $filename;
                }
            }
        }

        if ($messageType !== 'error') {
            try {
                $stmt = $pdo->prepare("SELECT id FROM followers WHERE LOWER(TRIM(BOTH '@' FROM username)) = ? AND id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetch()) {
                    $message = 'This username already exists.';
                    $messageType = 'error';
                    $editId = $id;
                } else {
                    $stmt = $pdo->prepare("UPDATE followers SET username = ?, name = ?, image = ? WHERE id = ?");
                    $stmt->execute([$username, $name ?: null, $imagePath, $id]);
                    $message = 'Follower updated successfully.';
                    $messageType = 'success';
                    $editId = null;
                }
            } catch (PDOException $e) {
                $message = 'Failed to update.';
                $messageType = 'error';
                $editId = $id;
            }
        }
    }
}

// Fetch follower for editing
if ($editId) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, name, image FROM followers WHERE id = ?");
        $stmt->execute([$editId]);
        $editing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$editing) $editId = null;
    } catch (PDOException $e) {
        $editId = null;
    }
}

// Fetch all followers
$followers = [];
try {
    $cols = ['id', 'username', 'name', 'image', 'created_at'];
    $stmt = $pdo->query("SELECT " . implode(', ', $cols) . " FROM followers ORDER BY created_at DESC");
    $followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $stmt = $pdo->query("SELECT id, instagram_id as username, created_at FROM followers ORDER BY created_at DESC");
        $followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $message = 'Could not load followers.';
        $messageType = 'error';
    }
}

$total = count($followers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Followers | Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body { background: linear-gradient(135deg, #f0f4ff 0%, #e8eef8 100%); min-height: 100vh; }
        .admin-wrap { max-width: 780px; margin: 0 auto; padding: 32px 24px; }
        .page-nav { margin-bottom: 24px; }
        .page-nav a { color: #1f41a8; text-decoration: none; font-weight: 500; }
        .page-nav a:hover { text-decoration: underline; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .admin-header h1 { color: #1f41a8; font-size: 1.5rem; margin: 0; }
        .card { background: #fff; border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 24px rgba(31,65,168,0.06); border: 1px solid rgba(31,65,168,0.06); }
        .card h2 { margin: 0 0 20px; font-size: 1.125rem; color: #1a1a2e; }
        .message { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; }
        .message.success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .message.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .edit-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 12px; }
        @media (max-width: 560px) { .edit-form { grid-template-columns: 1fr; } }
        .form-group { margin: 0; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #374151; }
        .form-group input { width: 100%; padding: 12px 16px; font-size: 15px; border: 1.5px solid #e5e7eb; border-radius: 10px; background: #fff; }
        .form-group input:focus { outline: none; border-color: #1f41a8; }
        .file-wrap input[type="file"] { padding: 10px; font-size: 14px; border: 1.5px dashed #d1d5db; border-radius: 10px; background: #fff; }
        .form-hint { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .btn { padding: 12px 24px; font-size: 15px; font-weight: 600; color: #fff; background: linear-gradient(135deg, #1f41a8 0%, #163580 100%); border: none; border-radius: 10px; cursor: pointer; }
        .btn:hover { box-shadow: 0 4px 12px rgba(31,65,168,0.3); }
        .btn-edit { padding: 6px 14px; font-size: 12px; font-weight: 500; background: #1f41a8; color: #fff; border: none; border-radius: 6px; text-decoration: none; display: inline-block; cursor: pointer; }
        .btn-edit:hover { background: #163580; }
        .btn-delete { padding: 8px 18px; font-size: 13px; font-weight: 600; background: #dc2626; color: #fff; border: none; border-radius: 8px; cursor: pointer; }
        .btn-delete:hover { background: #b91c1c; }
        .btn-delete-small { padding: 6px 12px; font-size: 12px; font-weight: 500; background: transparent; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer; }
        .btn-delete-small:hover { background: #fee2e2; }
        .btn-cancel { padding: 6px 14px; font-size: 12px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; text-decoration: none; display: inline-block; margin-left: 8px; }
        .btn-cancel:hover { background: #d1d5db; }
        .follower-list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 12px 16px; background: #f8fafc; border-radius: 10px; flex-wrap: wrap; gap: 12px; }
        .follower-list-header label { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; color: #374151; cursor: pointer; }
        .follower-list { max-height: 500px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: #fafbfc; }
        .follower-item { display: flex; align-items: center; padding: 12px 16px; font-size: 14px; gap: 14px; border-bottom: 1px solid #eee; }
        .follower-item:last-child { border-bottom: none; }
        .follower-item:hover { background: #fff; }
        .follower-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: #1f41a8; flex-shrink: 0; }
        .follower-item img, .follower-item .avatar-placeholder { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
        .avatar-placeholder { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: #4f46e5; }
        .follower-info { flex: 1; min-width: 0; }
        .follower-username { font-weight: 600; color: #1f41a8; }
        .follower-name { font-size: 13px; color: #6b7280; margin-left: 4px; }
        .follower-date { color: #9ca3af; font-size: 12px; flex-shrink: 0; }
        .follower-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .stats { font-size: 14px; color: #6b7280; margin-bottom: 16px; }
        .stats strong { color: #1f41a8; }
    </style>
</head>
<body>
    <div class="admin-wrap">
        <div class="page-nav">
            <a href="dashboard.php">← Dashboard</a>
            <a href="pick-winner.php" style="margin-left:16px">Pick Random Winner</a>
            <a href="../winners.php" style="margin-left:16px">Winners</a>
        </div>
        <div class="admin-header">
            <h1>Manage Followers</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($editing): ?>
        <div class="card">
            <h2>Edit Follower</h2>
            <form method="post" enctype="multipart/form-data" class="edit-form">
                <input type="hidden" name="edit_id" value="<?= (int) $editing['id'] ?>">
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" value="<?= htmlspecialchars($editing['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_name">Name</label>
                    <input type="text" id="edit_name" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
                </div>
                <div class="form-group full-width">
                    <label for="edit_image">Image</label>
                    <div class="file-wrap">
                        <input type="file" id="edit_image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <span class="form-hint">Max 10KB. Leave empty to keep current image.</span>
                    </div>
                </div>
                <div class="form-group full-width">
                    <button type="submit" class="btn">Update Follower</button>
                    <a href="manage-followers.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>All Followers</h2>
            <p class="stats">Total: <strong><?= $total ?></strong></p>
            <?php if (empty($followers)): ?>
                <p style="color:#888; padding:16px;">No followers yet.</p>
            <?php else: ?>
                <?php foreach ($followers as $row): ?>
                <form id="del-<?= (int) $row['id'] ?>" method="post" style="display:none">
                    <input type="hidden" name="delete_single" value="<?= (int) $row['id'] ?>">
                </form>
                <?php endforeach; ?>
                <form method="post" id="manage-form">
                    <div class="follower-list-header">
                        <label>
                            <input type="checkbox" id="select-all" title="Select all">
                            Select all
                        </label>
                        <button type="submit" name="delete_selected" class="btn-delete">Delete Selected</button>
                    </div>
                    <div class="follower-list">
                        <?php foreach ($followers as $row): ?>
                            <div class="follower-item">
                                <input type="checkbox" name="delete_ids[]" value="<?= (int) $row['id'] ?>" class="row-check">
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
                                <div class="follower-actions">
                                    <a href="manage-followers.php?edit=<?= (int) $row['id'] ?>" class="btn-edit">Edit</a>
                                    <button type="submit" form="del-<?= (int) $row['id'] ?>" class="btn-delete-small" onclick="return confirm('Delete this follower?');">Delete</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
                <script>
                    document.getElementById('select-all').addEventListener('change', function() {
                        document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = this.checked; }, this);
                    });
                    document.getElementById('manage-form').addEventListener('submit', function(e) {
                        if (e.submitter && e.submitter.name === 'delete_selected') {
                            var checked = document.querySelectorAll('.row-check:checked');
                            if (checked.length === 0) { e.preventDefault(); alert('Select at least one follower to delete.'); }
                            else if (!confirm('Delete ' + checked.length + ' follower(s)?')) e.preventDefault();
                        }
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
