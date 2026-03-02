<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare("SELECT id, password FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                login_admin($admin['id']);
                header('Location: admin/dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'A database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Goood Morning India</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-container { max-width: 400px; margin: 80px auto; padding: 32px; background: rgba(255,255,255,0.95); border-radius: 16px; box-shadow: 0 8px 32px rgba(31,65,168,0.15); }
        .login-container h1 { color: #1f41a8; margin-bottom: 24px; font-size: 1.5rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 12px 16px; font-size: 16px; border: 2px solid rgba(31,65,168,0.2); border-radius: 10px; }
        .form-group input:focus { outline: none; border-color: #1f41a8; }
        .btn { display: block; width: 100%; padding: 12px 20px; font-size: 16px; font-weight: 600; color: #fff; background: #1f41a8; border: none; border-radius: 10px; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #163580; }
        .error { padding: 12px; background: #fee2e2; color: #b91c1c; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .back-link { display: inline-block; margin-top: 16px; color: #1f41a8; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin Login</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Log In</button>
        </form>
        <a href="index.php" class="back-link">← Back to Home</a>
    </div>
</body>
</html>
