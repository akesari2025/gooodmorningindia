<?php
require_once __DIR__ . '/includes/db.php';

$winnersByDate = [];
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') . '/';
try {
    $pdo = get_db();
    $stmt = $pdo->query("SELECT id, username, name, image, winner_date FROM daily_winners ORDER BY winner_date DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = $row['winner_date'];
        if (!isset($winnersByDate[$date])) {
            $winnersByDate[$date] = [];
        }
        $winnersByDate[$date][] = $row;
    }
} catch (PDOException $e) {
    $winnersByDate = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Winners Archive | Goood Morning India</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .page-wrap { max-width: 900px; margin: 0 auto; padding: 24px 20px; flex: 1; display: flex; flex-direction: column; }
        .page-header { text-align: center; margin-bottom: 32px; }
        .page-header h1 { color: #1f41a8; font-size: 1.75rem; margin-bottom: 8px; }
        .page-nav { margin-bottom: 24px; }
        .page-nav a { color: #1f41a8; text-decoration: none; font-weight: 500; }
        .page-nav a:hover { text-decoration: underline; }
        .date-group { margin-bottom: 32px; }
        .date-group h2 { font-size: 1.1rem; color: #333; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
        .winners-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 16px; }
        .winner-card { display: flex; flex-direction: column; align-items: center; text-align: center; }
        .winner-card img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
        .winner-card .avatar-placeholder { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 600; color: #4f46e5; }
        .winner-card .winner-name { font-size: 13px; font-weight: 500; color: #1f41a8; margin-top: 8px; word-break: break-word; }
        .empty-state { text-align: center; padding: 48px 24px; color: #6b7280; }
        .page-footer { margin-top: auto; padding: 48px 24px 24px; text-align: center; }
        .page-footer a { color: #6b7280; text-decoration: none; font-size: 14px; }
        .page-footer a:hover { color: #1f41a8; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="page-nav">
            <a href="index.php">← Home</a>
            <a href="https://www.instagram.com/gooodmorning_india" target="_blank" rel="noopener noreferrer" style="margin-left:16px"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:5px"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.205.012-3.584.07-4.849.149-3.26 1.699-4.771 4.92-4.919 1.265-.058 1.644-.07 4.849-.07zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.667.072 4.947.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.667-.014 4.947-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>Follow on Instagram</a>
        </div>
        <div class="page-header">
            <h1>🏆 Daily Winners Archive 🎉🎊</h1>
            <p>🎊 Celebrating our community champions 🏆</p>
        </div>

        <?php if (empty($winnersByDate)): ?>
            <div class="empty-state">
                <p>No winners yet. Check back soon!</p>
            </div>
        <?php else: ?>
            <?php foreach ($winnersByDate as $date => $winners): ?>
                <div class="date-group">
                    <h2><?= date('F j, Y', strtotime($date)) ?></h2>
                    <div class="winners-grid">
                        <?php foreach ($winners as $w): ?>
                            <div class="winner-card">
                                <?php if (!empty($w['image'])): ?>
                                    <?php $imgUrl = (strpos($w['image'], 'http') === 0) ? $w['image'] : $baseUrl . ltrim($w['image'], '/'); ?>
                                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><?= strtoupper(mb_substr($w['username'] ?? '', 0, 1)) ?: '?' ?></div>
                                <?php endif; ?>
                                <span class="winner-name"><?= htmlspecialchars($w['username']) ?></span>
                                <?php if (!empty($w['name'])): ?>
                                    <span style="font-size:12px;color:#6b7280"><?= htmlspecialchars($w['name']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <footer class="page-footer">
            <a href="rules.php">Rules & Terms</a>
            <a href="https://www.instagram.com/gooodmorning_india" target="_blank" rel="noopener noreferrer" style="margin-left:16px"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:5px"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.205.012-3.584.07-4.849.149-3.26 1.699-4.771 4.92-4.919 1.265-.058 1.644-.07 4.849-.07zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.667.072 4.947.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.667-.014 4.947-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>Follow on Instagram</a>
        </footer>
    </div>
</body>
</html>
