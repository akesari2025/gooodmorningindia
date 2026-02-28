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
    <meta name="description" content="Good Morning India - A vibrant community celebrating Indian culture and connection.">
    <title>Good Morning India | GooodMorningIndia.com</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Main Hero Section -->
    <main>
        <div class="site-logo">
            <img src="images/gooodmorningindia_logo.png" alt="Good Morning India" class="logo-img">
        </div>
        <div class="hero-card">
            <h1>Good Morning India</h1>
            <p class="subtitle">Follow. Featured. Win. <span class="flag-emoji">🎁☕️</span></p>
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
    </main>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.GOOD_MORNING_BADGES = <?= json_encode($followers) ?>;
    </script>
    <script src="index.js"></script>
</body>
</html>
