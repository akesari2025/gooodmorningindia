<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = get_db();
$message = '';
$messageType = '';
$winner = null;
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if (substr($baseUrl, -1) !== '/') $baseUrl .= '/';

$isExistingWinner = false;

// Handle random winner selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['winner_date'])) {
    $winnerDate = trim($_POST['winner_date'] ?? '');
    if ($winnerDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $winnerDate)) {
        try {
            // Check if this date already has a winner
            $stmt = $pdo->prepare("SELECT username, name, image FROM daily_winners WHERE winner_date = ? LIMIT 1");
            $stmt->execute([$winnerDate]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $winner = $existing;
                $isExistingWinner = true;
                $displayName = trim($winner['name'] ?? $winner['username']) ?: $winner['username'];
                $message = '🎉 Winner of the day is ' . htmlspecialchars($displayName) . ' 🏆🎊';
                $messageType = 'success';
            } else {
                // Pick new random winner from followers
                try {
                    $stmt = $pdo->query("SELECT id, username, name, image FROM followers ORDER BY RAND() LIMIT 1");
                } catch (PDOException $e) {
                    $stmt = $pdo->query("SELECT id, instagram_id as username, created_at FROM followers ORDER BY RAND() LIMIT 1");
                }
                $winner = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($winner) {
                    $stmt = $pdo->prepare("INSERT INTO daily_winners (username, name, image, winner_date) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $winner['username'],
                        $winner['name'] ?? null,
                        $winner['image'] ?? null,
                        $winnerDate
                    ]);
                    $message = 'Winner selected and saved to archive!';
                    $messageType = 'success';
                } else {
                    $message = 'No followers found. Add followers first.';
                    $messageType = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = 'Failed to select winner: ' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
            $winner = null;
        }
    } else {
        $message = 'Please select a valid date.';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pick Random Winner | Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        body { background: linear-gradient(135deg, #f0f4ff 0%, #e8eef8 100%); min-height: 100vh; }
        .page-wrap { max-width: 520px; margin: 0 auto; padding: 32px 24px; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { color: #1f41a8; font-size: 1.5rem; margin: 0 0 8px 0; }
        .page-nav { margin-bottom: 24px; }
        .page-nav a { color: #1f41a8; text-decoration: none; font-weight: 500; }
        .page-nav a:hover { text-decoration: underline; }
        .card { background: #fff; border-radius: 16px; padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 24px rgba(31,65,168,0.06); border: 1px solid rgba(31,65,168,0.06); }
        .card h2 { margin: 0 0 20px; font-size: 1.125rem; color: #1a1a2e; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #374151; }
        .form-group input[type="date"] { width: 100%; padding: 12px 16px; font-size: 15px; border: 1.5px solid #e5e7eb; border-radius: 10px; background: #fafafa; }
        .btn { padding: 14px 32px; font-size: 16px; font-weight: 600; color: #fff; background: linear-gradient(135deg, #1f41a8 0%, #163580 100%); border: none; border-radius: 10px; cursor: pointer; box-shadow: 0 2px 8px rgba(31,65,168,0.25); transition: transform 0.15s, box-shadow 0.2s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(31,65,168,0.3); }
        .message { padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; }
        .message.success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .message.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .winner-box { text-align: center; padding: 32px 24px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 16px; margin-top: 24px; animation: winnerPop 0.6s ease; }
        @keyframes winnerPop { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .winner-box h3 { color: #92400e; font-size: 1.5rem; margin: 0 0 24px 0; }
        .winner-box .winner-photo { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #f59e0b; margin-bottom: 16px; }
        .winner-box .winner-photo-placeholder { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 700; color: #4f46e5; margin: 0 auto 16px; }
        .winner-box .winner-name { font-size: 1.25rem; font-weight: 700; color: #1f41a8; margin-bottom: 4px; }
        .winner-box .winner-username { font-size: 1rem; color: #6b7280; }
        .winner-box.initially-hidden { display: none !important; }
        .winner-box.revealed { display: block !important; animation: winnerPop 0.6s ease; }
        .countdown-overlay { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%); background-size: 400% 400%; animation: overlayPulse 4s ease infinite; }
        .countdown-overlay.hidden { display: none; }
        .countdown-content { text-align: center; color: #fff; display: flex; flex-direction: column; align-items: center; }
        .countdown-logo { width: clamp(80px, 18vw, 140px); height: auto; border-radius: 50%; margin-bottom: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        .countdown-title { font-size: clamp(20px, 5vw, 32px); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 24px; opacity: 0.98; text-shadow: 0 2px 12px rgba(0,0,0,0.4); }
        .countdown-label { font-size: clamp(14px, 3vw, 18px); font-weight: 600; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 12px; opacity: 0.95; text-shadow: 0 2px 8px rgba(0,0,0,0.3); }
        .countdown-number { font-size: clamp(120px, 25vw, 220px); font-weight: 900; line-height: 1; text-shadow: 0 4px 30px rgba(0,0,0,0.4); letter-spacing: -4px; animation: countdownPop 1s ease; }
        .countdown-subtext { font-size: clamp(12px, 2.5vw, 16px); margin-top: 16px; opacity: 0.9; text-shadow: 0 1px 4px rgba(0,0,0,0.2); }
        @keyframes overlayPulse { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        @keyframes countdownPop { 0% { transform: scale(0.5); opacity: 0.5; } 70% { transform: scale(1.08); } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>
    <?php if ($winner): ?>
    <div id="countdown-overlay" class="countdown-overlay">
        <div class="countdown-content">
            <img src="../images/gooodmorningindia_logo.png" alt="Goood Morning India" class="countdown-logo">
            <h2 class="countdown-title">Goood Morning India</h2>
            <div class="countdown-label">Selecting random winner…</div>
            <div id="countdown-number" class="countdown-number">30</div>
            <div class="countdown-subtext">seconds</div>
        </div>
    </div>
    <?php endif; ?>
    <div class="page-wrap">
        <div class="page-nav">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../winners.php" style="margin-left:16px">Winners Archive</a>
        </div>
        <div class="page-header">
            <h1>Pick Random Winner</h1>
            <p style="color:#6b7280;margin:0">Let our AI work its magic — one tap, one lucky winner! ✨🏆</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>We are announcing our today's winner</h2>
            <form method="post">
                <p class="today-date" style="font-size:15px;font-weight:600;color:#1f41a8;margin:0 0 20px 0;"><?= date('l, F j, Y') ?></p>
                <input type="hidden" name="winner_date" value="<?= date('Y-m-d') ?>">
                <button type="submit" class="btn">Select Random Winner</button>
            </form>

            <?php if ($winner): ?>
                <div id="winner-box" class="winner-box initially-hidden">
                    <h3><?= $isExistingWinner ? '🏆 Winner of the day 🎉🎊' : '🎉 Congratulations! 🏆🎊' ?></h3>
                    <?php if (!empty($winner['image'])): ?>
                        <?php $imgUrl = (strpos($winner['image'], 'http') === 0) ? $winner['image'] : $baseUrl . ltrim($winner['image'], '/'); ?>
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="winner-photo">
                    <?php else: ?>
                        <div class="winner-photo-placeholder"><?= strtoupper(mb_substr($winner['username'] ?? '', 0, 1)) ?: '?' ?></div>
                    <?php endif; ?>
                    <div class="winner-name"><?= htmlspecialchars($winner['name'] ?: $winner['username']) ?></div>
                    <div class="winner-username"><?= htmlspecialchars((strpos($winner['username'], '@') === 0 ? '' : '@') . $winner['username']) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($winner): ?>
    <script>
    (function() {
        var overlay = document.getElementById('countdown-overlay');
        var numberEl = document.getElementById('countdown-number');
        var winnerBox = document.getElementById('winner-box');
        var sec = 30;
        function tick() {
            numberEl.textContent = sec;
            numberEl.style.animation = 'none';
            numberEl.offsetHeight;
            numberEl.style.animation = 'countdownPop 1s ease';
            if (sec <= 0) {
                overlay.classList.add('hidden');
                winnerBox.classList.remove('initially-hidden');
                winnerBox.classList.add('revealed');
                return;
            }
            sec--;
            setTimeout(tick, 1000);
        }
        tick();
    })();
    </script>
    <?php endif; ?>
</body>
</html>
