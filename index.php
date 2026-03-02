<?php
require_once __DIR__ . '/includes/db.php';

$followers = [];
try {
    $pdo = get_db();
    $stmt = $pdo->query("SELECT username, name, image FROM followers ORDER BY RAND()");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user = $row['username'];
        $followers[] = [
            'username' => (strpos($user, '@') === 0) ? $user : ('@' . $user),
            'name'     => $row['name'] ?? '',
            'image'    => $row['image'] ?? '',
        ];
    }
} catch (PDOException $e) {
    try {
        $stmt = $pdo->query("SELECT instagram_id FROM followers ORDER BY RAND()");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['instagram_id'];
            $followers[] = [
                'username' => (strpos($id, '@') === 0) ? $id : ('@' . $id),
                'name'     => '',
                'image'    => '',
            ];
        }
    } catch (PDOException $e2) {
        $followers = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Goood Morning India - A vibrant community celebrating Indian culture and connection.">
    <title>Goood Morning India | GooodMorningIndia.com</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Main Hero Section -->
    <main>
        <div class="site-logo">
            <img src="images/gooodmorningindia_logo.png" alt="Goood Morning India" class="logo-img">
        </div>
        <div class="hero-card">
            <h1>Goood Morning India</h1>
            <p class="subtitle">Follow. Featured. Win. <span class="flag-emoji">🎁☕️</span></p>
            <nav class="main-nav">
                <a href="winners.php">🏆 Winners</a>
                <a href="https://www.instagram.com/gooodmorning_india" target="_blank" rel="noopener noreferrer"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:5px"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.205.012-3.584.07-4.849.149-3.26 1.699-4.771 4.92-4.919 1.265-.058 1.644-.07 4.849-.07zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.667.072 4.947.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.667-.014 4.947-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>Instagram</a>
            </nav>
        </div>

        <!-- Search Field -->
        <div class="search-container">
            <input 
                type="text" 
                id="search-input" 
                class="search-field" 
                placeholder="Search your username..."
                aria-label="Search usernames"
            >
            <span class="search-icon">🔍</span>
        </div>

        <!-- Badge Box Container -->
        <div class="badge-box">
            <div id="badge-layer"></div>
        </div>

        <footer class="site-footer">
            <a href="rules.php">Rules & Terms</a>
            <a href="https://www.instagram.com/gooodmorning_india" target="_blank" rel="noopener noreferrer" style="margin-left:16px"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:5px"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.205.012-3.584.07-4.849.149-3.26 1.699-4.771 4.92-4.919 1.265-.058 1.644-.07 4.849-.07zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.667.072 4.947.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.667-.014 4.947-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>Follow on Instagram</a>
        </footer>
    </main>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.GOOD_MORNING_BADGES = <?= json_encode($followers) ?>;
    </script>
    <script src="index.js"></script>
</body>
</html>
