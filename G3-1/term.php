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
    $user = $stmt->fetch();

    if (!$user) {
        echo "ユーザー情報が見つかりません。";
        exit;
    }

     // Roleテーブルから全役職を取得
     $role_query = "SELECT * FROM Role ORDER BY repuired_status DESC";
     $role_stmt = $conn->query($role_query);
     $roles = $role_stmt->fetchAll();
 
     $new_role_id = $user['role_id'];  // 役職の初期化（デフォルトは現在の役職）
 
     foreach ($roles as $role) {
         if ($user['total_score'] >= $role['repuired_status'] &&
             $user['trust_level'] >= $role['repuired_trust'] &&
             $user['technical_skill'] >= $role['repuired_technical'] &&
             $user['negotiation_skill'] >= $role['repuired_negotiation'] &&
             $user['appearance'] >= $role['repuired_appearance'] &&
             $user['popularity'] >= $role['repuired_popularity']
         ) {
             $new_role_id = $role['role_id'];
             break;
         }
     }


    // Careerテーブルからcurrent_termとcurrent_monthsを取得
    $career_query = "SELECT current_term, current_months FROM Career WHERE user_id = :user_id";
    $career_stmt = $conn->prepare($career_query);
    $career_stmt->execute([':user_id' => $user_id]);
    $career = $career_stmt->fetch();

    // 現在の役職を取得
    $current_role_query = "SELECT role_name, role_explanation FROM Role WHERE role_id = :role_id";
    $current_role_stmt = $conn->prepare($current_role_query);
    $current_role_stmt->execute([':role_id' => $new_role_id]);
    $current_role = $current_role_stmt->fetch();
    


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
        <link rel="stylesheet" href="css/term.css">
        <title>ターム</title>
    </head>
<body>
    <div class="center-container">
        <div class="content">
            <!-- ここにDBからのそれぞれのテキストを追加 -->
            <h1><?php echo $career['current_term']; ?>年目終了</h1>
            <p>1ターム目</p>
            <h2><?php echo htmlspecialchars($current_role['role_name']); ?></h2>
            <h3>信頼度：<span><?php echo $user['trust_level']; ?></span><br>
                技術力：<span><?php echo $user['technical_skill']; ?></span><br>
                交渉力：<span><?php echo $user['negotiation_skill']; ?></span><br>
                容　姿：<span><?php echo $user['appearance']; ?></span><br>
                好感度：<span><?php echo $user['popularity']; ?></span><br>
            </h3>

            <button class="roundbutton" id="nextButton">つぎへ</button>
    </div>
    <script>
        document.getElementById('nextButton').addEventListener('click', function () {
            window.location.href = '../G2-1/home.php';
        });
    </script>
</body>
</html>