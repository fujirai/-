<?php
session_start();
require '../db.php';
$userID = 1;
try {
    $pdo = connectDB();

    // ステータス情報を取得するクエリ
    $stmt = $pdo->prepare('SELECT u.user_name, s.* FROM Status s JOIN User u ON s.status_id = u.user_id WHERE user_id = :userID');
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->execute();

    $status = $stmt->fetch();
    if (!$status) {
        $status = [
            'user_name' => '名無し',
            'trust_level' => 0,
            'technical_skill' => 0,
            'negotiation_skill' => 0,
            'appearance' => 0,
            'popularity' => 0,
        ];
    }
    $stmt = $pdo->prepare(
        'SELECT role_name 
        FROM Role 
        WHERE repuired_trust <= :trust 
        AND repuired_technical <= :technical 
        AND repuired_negotiation <= :negotiation 
        AND repuired_apparance <= :appearance 
        AND repuired_popularity <= :popularity 
        ORDER BY role_id DESC 
        LIMIT 1'
    );
    $role = $stmt->fetchColumn();
    if (!$role) {
        $role = '役職なし';
    }
    $stmt->execute([
        ':trust' => $status['trust_level'],
        ':technical' => $status['technical_skill'],
        ':negotiation' => $status['negotiation_skill'],
        ':appearance' => $status['apparance'],
        ':popularity' => $status['popularity']
    ]);
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/gameend.css" />
        <title>オープニング画面</title>
    </head>
    <body>
        <h1>GAME CLEAR</h1>
        <div class="clear-status">
            <div class="nobr">
                <p class="user_name"><?= htmlspecialchars($status['user_name']) ?></p>
                <p class="role_name"><?= htmlspecialchars($role) ?></p>
            </div><div class="status">
                <p class="trust_level">信頼度：<?= htmlspecialchars($status['trust_level']) ?></p>
                <p class="technical_skill">技術力：<?= htmlspecialchars($status['technical_skill']) ?></p>
                <p class="negotiation_skill">交渉力：<?= htmlspecialchars($status['negotiation_skill']) ?></p>
                <p class="appearance">容姿：<?= htmlspecialchars($status['apparance']) ?></p>
                <p class="popularity">好感度：<?= htmlspecialchars($status['popularity']) ?></p>
            </div>
        </div>
        <button class="ranking-button" onclick="location.href='../G4-3/ranking.html'">ランキング</button>
        <button class="title-button" onclick="location.href='../G1-0/index.html'">タイトル</button>
    </body>
</html>