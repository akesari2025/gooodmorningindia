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
    <title>Daily Winners Archive | Good Morning India</title>
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
        </div>
        <div class="page-header">
            <h1>Daily Winners Archive</h1>
            <p>Celebrating our community champions</p>
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
        </footer>
    </div>
</body>
</html>
