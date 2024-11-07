<?php
session_start();
require_once __DIR__ . '/../db.php';   // DB接続ファイルをインクルード

if (!isset($_SESSION['user_id'])) {
    header("Location: ../G1-0/index.html");
    exit;
}

try {
    $conn = connectDB();
    $user_id = $_SESSION['user_id'];

    // ユーザーデータとステータスを取得
    $query = "SELECT User.user_name, Status.trust_level, Status.technical_skill, 
                     Status.negotiation_skill, Status.appearance, Status.popularity, 
                     Status.total_score, User.role_id 
              FROM User 
              JOIN Status ON User.status_id = Status.status_id 
              WHERE User.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("ユーザー情報が見つかりません。");
    }

    // 現在の役職を取得
    $current_role_query = "SELECT role_name FROM Role WHERE role_id = :role_id";
    $current_role_stmt = $conn->prepare($current_role_query);
    $current_role_stmt->execute([':role_id' => $user['role_id']]);
    $current_role = $current_role_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_role) {
        throw new Exception("役職情報が見つかりません。");
    }

    // Careerテーブルから現在のタームと月を取得
    $career_query = "SELECT current_term, current_months FROM Career WHERE user_id = :user_id";
    $career_stmt = $conn->prepare($career_query);
    $career_stmt->execute([':user_id' => $user_id]);
    $career = $career_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$career) {
        throw new Exception("Career情報が見つかりません。");
    }

    $current_term = $career['current_term'];
    $current_month = $career['current_months'];

    // ターム終了のフラグ
    $isFinalMonth = ($current_month === 3);
    $nextTerm = $current_term + 1;

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
    <link rel="stylesheet" href="css/term.css">
    <title>ターム</title>
</head>
<body>
    <div class="center-container">
        <div class="content">
            <h2>
                <?php 
                    // ターム結果表示用
                    $previous_term = $current_term - 1; // 現在のタームから-1
                    echo htmlspecialchars($previous_term) . "年目の結果"; 
                ?>
            </h2>
            <!-- ユーザー情報を表示 -->
            <h1><?php echo htmlspecialchars($user['user_name']); ?> のステータス</h1>
            <p>役職: <strong><?php echo htmlspecialchars($current_role['role_name']); ?></strong></p>
            <h3>
                信頼度: <?php echo $user['trust_level']; ?><br>
                技術力: <?php echo $user['technical_skill']; ?><br>
                交渉力: <?php echo $user['negotiation_skill']; ?><br>
                容姿: <?php echo $user['appearance']; ?><br>
                好感度: <?php echo $user['popularity']; ?><br>
            </h3>

            <!-- 条件に応じたボタン表示 -->
            <?php if ($isFinalMonth): ?>
                <button class="roundbutton" onclick="location.href='../G4-1/ending.php'">エンディングへ</button>
            <?php else: ?>
                <button class="roundbutton" onclick="location.href='../G2-1/home.php'">次のタームへ</button>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
