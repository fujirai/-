<?php
session_start();
require_once '../db.php';

try {
    $pdo = connectDB();
    $user_id = 136;

    // ユーザーのステータス情報を取得
    $stmt = $pdo->prepare(
        'SELECT u.user_name, s.trust_level, s.technical_skill, s.negotiation_skill, 
                s.appearance, s.popularity, s.total_score
         FROM Status s
         JOIN User u ON s.status_id = u.status_id
         WHERE u.user_id = :user_id'
    );
    $stmt->execute([':user_id' => $user_id]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$status) {
        throw new Exception("ステータス情報が見つかりません。");
    }

    // Roleテーブルから役職情報を取得
    $stmt = $pdo->prepare(
        'SELECT role_name 
         FROM Role 
         WHERE repuired_trust <= :trust 
         AND repuired_technical <= :technical 
         AND repuired_negotiation <= :negotiation 
         AND repuired_appearance <= :appearance 
         AND repuired_popularity <= :popularity 
         ORDER BY repuired_status DESC 
         LIMIT 1'
    );
    $stmt->execute([
        ':trust' => $status['trust_level'],
        ':technical' => $status['technical_skill'],
        ':negotiation' => $status['negotiation_skill'],
        ':appearance' => $status['appearance'],
        ':popularity' => $status['popularity']
    ]);
    $role = $stmt->fetchColumn();

    if (!$role) {
        $role = '役職なし';
    }

} catch (PDOException $e) {
    echo "データベースエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
} catch (Exception $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/gameend.css">
    <title>ゲーム終了</title>
</head>
<body>
    <div id="fly-in">
        <div><span></span>GAME CLEAR</div>
    </div>
    <div class="nobr">
        <p class="user_name"><?= htmlspecialchars($status['user_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="role_name"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="status">
        <p class="trust_level">信頼度：<?= htmlspecialchars($status['trust_level'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="technical_skill">技術力：<?= htmlspecialchars($status['technical_skill'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="negotiation_skill">交渉力：<?= htmlspecialchars($status['negotiation_skill'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="appearance">容姿：<?= htmlspecialchars($status['appearance'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="popularity">好感度：<?= htmlspecialchars($status['popularity'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="total_score">合計スコア：<?= htmlspecialchars($status['total_score'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="buttons">
        <button class="ranking-button" onclick="location.href='../G4-3/ranking.php?from=gameend'">ランキング</button>
        <button class="title-button" onclick="location.href='../G1-0/index.html'">タイトル</button>
    </div>
</body>
</html>
