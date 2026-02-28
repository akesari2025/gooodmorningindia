<?php
header('Content-Type: application/json');

$followers = [];
try {
    require_once __DIR__ . '/includes/db.php';
    $pdo = get_db();
    $stmt = $pdo->query("SELECT username, name, image FROM followers ORDER BY RAND()");
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    if (substr($baseUrl, -1) !== '/') $baseUrl .= '/';

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user = $row['username'];
        $image = $row['image'] ?? '';
        if ($image) {
            $image = $baseUrl . ltrim($image, '/');
        }
        $followers[] = [
            'username' => (strpos($user, '@') === 0) ? $user : ('@' . $user),
            'name'     => $row['name'] ?? '',
            'image'    => $image,
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
    } catch (PDOException $e2) {}
}

echo json_encode($followers);
