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

    // Current stats from the database
$current_stats = [
    'trust' => $user['trust_level'],
    'technical' => $user['technical_skill'],
    'negotiation' => $user['negotiation_skill'],
    'appearance' => $user['appearance'],
    'popularity' => $user['popularity']
];

// セッションから以前のステータスを取得（利用可能でない場合は現在のステータスで初期化）
$previous_stats = $_SESSION['previous_stats'] ?? $current_stats;

// ステイタス変動の計算
$stat_changes = [];
foreach ($current_stats as $key => $value) {
    $change = $value - $previous_stats[$key];
    if ($change > 0) {
        $stat_changes[$key] = "<span class='stat-up'>▲{$change}</span>";
    } elseif ($change < 0) {
        $stat_changes[$key] = "<span class='stat-down'>▼" . abs($change) . "</span>";
    } else {
        $stat_changes[$key] = ""; // No change
    }
}

// Update session with current stats for future reference
$_SESSION['previous_stats'] = $current_stats;


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

    // 月の進行とタームの進行を分けて管理
    if ($current_month == 12) {  // 12月が終わった場合
        $new_month = 1;  // 1月に戻る
    } else {
        $new_month = $current_month + 1;  // 次の月へ進む
    }

    if ($new_month == 4) {  // 4月になった場合
        $new_term = $current_term + 1;  // 新しいタームに進む
    } else {
        $new_term = $current_term;
    }

    // イベント取得処理
    $event_query = "SELECT e.*, p.event_trust, p.event_technical, p.event_negotiation, 
                           p.event_appearance, p.event_popularity
                    FROM Event e
                    JOIN Point p ON e.event_id = p.event_id
                    WHERE e.event_term = :current_term AND e.event_months = :current_month
                    ORDER BY RAND() LIMIT 1";
    $event_stmt = $conn->prepare($event_query);
    $event_stmt->execute([
        ':current_term' => $current_term,
        ':current_month' => $current_month
    ]);
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception("該当するイベントが見つかりません。");
    }

    // // イベントの影響をStatusに反映
    // $status_update = "UPDATE Status SET 
    //                   trust_level = trust_level + :event_trust, 
    //                   technical_skill = technical_skill + :event_technical, 
    //                   negotiation_skill = negotiation_skill + :event_negotiation, 
    //                   appearance = appearance + :event_appearance, 
    //                   popularity = popularity + :event_popularity 
    //                   WHERE status_id = (SELECT status_id FROM User WHERE user_id = :user_id)";
    // $status_stmt = $conn->prepare($status_update);
    // $status_stmt->execute([
    //     ':event_trust' => $event['event_trust'],
    //     ':event_technical' => $event['event_technical'],
    //     ':event_negotiation' => $event['event_negotiation'],
    //     ':event_appearance' => $event['event_appearance'],
    //     ':event_popularity' => $event['event_popularity'],
    //     ':user_id' => $user_id
    // ]);

    // イベントの影響をStatusに反映
    $status_update = "UPDATE Status SET 
        trust_level = GREATEST(LEAST(trust_level + :event_trust, 100), -10), 
        technical_skill = GREATEST(LEAST(technical_skill + :event_technical, 100), -10), 
        negotiation_skill = GREATEST(LEAST(negotiation_skill + :event_negotiation, 100), -10), 
        appearance = GREATEST(LEAST(appearance + :event_appearance, 100), -10), 
        popularity = GREATEST(LEAST(popularity + :event_popularity, 100), -10) 
        WHERE status_id = (SELECT status_id FROM User WHERE user_id = :user_id)";
    $status_stmt = $conn->prepare($status_update);
    $status_stmt->execute([
        ':event_trust' => $event['event_trust'],
        ':event_technical' => $event['event_technical'],
        ':event_negotiation' => $event['event_negotiation'],
        ':event_appearance' => $event['event_appearance'],
        ':event_popularity' => $event['event_popularity'],
        ':user_id' => $user_id
    ]);

    // total_scoreの更新
    $score_update = "UPDATE Status 
                     SET total_score = trust_level + technical_skill + negotiation_skill + appearance + popularity 
                     WHERE status_id = (SELECT status_id FROM User WHERE user_id = :user_id)";
    $score_stmt = $conn->prepare($score_update);
    $score_stmt->execute([':user_id' => $user_id]);

    // Careerテーブルのcurrent_termとcurrent_monthsを更新
    $update_career_query = "UPDATE Career SET current_term = :new_term, current_months = :new_month WHERE user_id = :user_id";
    $update_career_stmt = $conn->prepare($update_career_query);
    $update_career_stmt->execute([
        ':new_term' => $new_term,
        ':new_month' => $new_month,
        ':user_id' => $user_id
    ]);

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
        <link rel="stylesheet" href="css/randamu.css">
        <title>ランダムイベント</title>
    </head>
<body>
    <!-- ステイタス表示と変動 -->
    <div id="popup" class="popup">
        <h2><p><span class="rotate-text">ステータス</span></p></h2>
        <p><h2><?php echo htmlspecialchars($current_role['role_name']); ?></h2></p>
        <p><h1><?php echo htmlspecialchars($user['user_name']); ?></h1></p>
        <p><h3>
            信頼度：<span><?php echo $user['trust_level']; ?></span> 
            <span><?php echo $stat_changes['trust']; ?></span><br>
            
            技術力：<span><?php echo $user['technical_skill']; ?></span> 
            <span><?php echo $stat_changes['technical']; ?></span><br>
            
            交渉力：<span><?php echo $user['negotiation_skill']; ?></span> 
            <span><?php echo $stat_changes['negotiation']; ?></span><br>
            
            容姿：<span><?php echo $user['appearance']; ?></span> 
            <span><?php echo $stat_changes['appearance']; ?></span><br>
            
            好感度：<span><?php echo $user['popularity']; ?></span> 
            <span><?php echo $stat_changes['popularity']; ?></span><br>
        </h3></p>
    </div>
    <div class="fixed-title">
        <h1>イベント: <?php echo htmlspecialchars($event['event_name']); ?></h1>
    </div>
    <div class="footer-box">
        <h2><?php echo htmlspecialchars($event['event_description']); ?></h2>
    </div>

    <!-- 通常時の戻るボタン -->
    <?php if (!($current_term == 4 && $current_month == 3)): ?>
        <div id="modo" class="modo" style="display: none;">
            <button id="backButton" class="game-button">戻る</button>
        </div>
    <?php endif; ?>

    <!-- 4ターム目の3月用エンディングボタン -->
    <?php if ($current_term == 4 && $current_month == 3): ?>
        <div id="endingButton" class="endingButton" style="display: none;">
            <button id="endButton" class="game-button">エンディングへ</button>
        </div>
    <?php endif; ?>

    <!-- 次のタームへ進むボタン -->
    <?php if ($current_month == 3 && $current_term != 4): ?>
        <div id="nextTermButton" class="nextTermButton" style="display: none;">
            <button id="next-term" class="game-button">1年間を終える</button>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // ポップアップ表示/非表示機能
            const popup = document.getElementById("popup");
            popup.addEventListener("click", function () {
                popup.classList.toggle("show");
            });

            const textElement = document.querySelector(".footer-box h2");
            const text = textElement.textContent;
            textElement.textContent = "";
            let i = 0;

            function type() {
                if (i < text.length) {
                    textElement.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, 25);
                } else {
                    if (<?php echo $current_term; ?> == 4 && <?php echo $current_month; ?> == 3) {
                        document.getElementById("endingButton").style.display = "block";
                    } else if (<?php echo $current_month; ?> == 3 && <?php echo $current_term; ?> != 4) {
                        document.getElementById("nextTermButton").style.display = "block";
                    } else {
                        document.getElementById("modo").style.display = "block";
                    }
                }
            }

            type();

            // 戻るボタンの動作
            const backButton = document.getElementById("backButton");
            if (backButton) {
                backButton.addEventListener("click", function () {
                    window.location.href = '../G2-1/home.php';
                });
            }

            // エンディングボタンの動作
            const endButton = document.getElementById("endButton");
            if (endButton) {
                endButton.addEventListener("click", function () {
                    window.location.href = '../G4-1/ending.php';
                });
            }

            // 次のタームへ進むボタンの動作
            const nextTermButton = document.getElementById("next-term");
            if (nextTermButton) {
                nextTermButton.addEventListener("click", function () {
                    window.location.href = 'term.php';
                });
            }
        });
    </script>
</body>
</html>