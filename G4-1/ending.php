<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../G1-0/index.html");
    exit;
}

try {
    $pdo = connectDB();
    $user_id = $_SESSION['user_id'];

    // ユーザー情報を取得
    $stmt = $pdo->prepare(
        'SELECT u.user_name, s.trust_level, s.technical_skill, s.negotiation_skill, 
                s.appearance, s.popularity, r.role_name , r.role_id
         FROM Status s
         JOIN User u ON s.status_id = u.status_id
         JOIN Role r ON u.role_id = r.role_id
         WHERE u.user_id = :user_id'
    );
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("ユーザー情報が見つかりません。");
    }

    // ユーザー情報をセッションに保持（ランキング表示などで使用）
    $_SESSION['user_name'] = $user['user_name'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['trust_level'] = $user['trust_level'];
    $_SESSION['technical_skill'] = $user['technical_skill'];
    $_SESSION['negotiation_skill'] = $user['negotiation_skill'];
    $_SESSION['appearance'] = $user['appearance'];
    $_SESSION['popularity'] = $user['popularity'];


    // ゲーム終了時に game_situation を 'end' に更新
    $updateQuery = "UPDATE User SET game_situation = 'end' WHERE user_id = :user_id";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([':user_id' => $user_id]);
     // role_id に応じて動画ファイルを選択
     if (isset($user['role_id']) && in_array($user['role_id'], [1, 8])) {
        $videoFile = 'BADEND.mp4';
    } elseif (isset($user['role_id']) && in_array($user['role_id'], [2, 3, 4])) {
        $videoFile = 'NORMALEND.mp4';
    } elseif (isset($user['role_id']) && in_array($user['role_id'], [5, 6, 7])) {
        $videoFile = 'HAPPYEND.mp4';
    } else {
        throw new Exception("適切なエンディング動画が見つかりません。");
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
    <link rel="stylesheet" href="css/ending.css">
    <title>エンディング</title>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const videoElement = document.querySelector("video");
            
            // 動画終了後に1秒待って遷移
            videoElement.addEventListener("ended", function () {
                setTimeout(() => {
                    window.location.href = '../G4-2/gameend.php';
                }, 1000);
            });
        });
    </script>
</head>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/ending.css">
    <title>エンディング</title>
</head>
<body>
    <div class="ending-container">
        <h1>エンディング</h1>
        <video autoplay controls>
            <source src="<?php echo htmlspecialchars($videoFile, ENT_QUOTES, 'UTF-8'); ?>" type="video/mp4">
            このブラウザでは動画が再生できません。
        </video>
    </div>
</body>
</html>