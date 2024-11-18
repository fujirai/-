<?php
session_start();
require_once __DIR__ . '/../db.php';   // DB接続ファイルをインクルード

$conn = connectDB();
$user_id = 136; // 仮のユーザーID（本番ではセッションから取得）

// ユーザーデータを取得
$query = "SELECT 
                    User.user_name, 
                    Status.trust_level, 
                    Status.technical_skill, 
                    Status.negotiation_skill, 
                    Status.appearance, 
                    Status.popularity, 
                    Status.total_score, 
                    Role.role_name 
            FROM User 
            JOIN Status ON User.status_id = Status.status_id 
            JOIN Role ON User.role_id = Role.role_id
            WHERE User.user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_data) {
    throw new Exception("ユーザー情報が見つかりません。");
}

// Careerテーブルから現在のタームと月を取得
$query = "SELECT current_term, current_months 
            FROM Career 
            WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$career_data = $stmt->fetch(PDO::FETCH_ASSOC);
$current_term = 1;
$current_month = 5;

if (!$career_data) {
    throw new Exception("Career情報が見つかりません。");
}

// イベントを取得
$event_query = "SELECT e.*, p.event_trust, p.event_technical, p.event_negotiation, 
                           p.event_appearance, p.event_popularity, p.border
                    FROM Event e
                    JOIN Point p ON e.event_id = p.event_id
                    WHERE e.event_term = :current_term AND e.event_months = :current_month
                    ORDER BY RAND() LIMIT 1";
$event_stmt = $conn->prepare($event_query);
$event_stmt->execute([
    ':current_term' => $current_term,
    ':current_month' => $current_term
]);
$event = $event_stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    throw new Exception("該当するイベントが見つかりません。");
}

// `border`が1の場合に`border_key = 1`のイベントを取得
if ($event['border'] == 1) {
    $border_key_query = "SELECT * FROM Point 
                         WHERE event_id = :event_id AND border = 1 AND border_key = 1";
    $border_key_stmt = $conn->prepare($border_key_query);
    $border_key_stmt->execute([':event_id' => 2 ]);
    $border_key_event = $border_key_stmt->fetch(PDO::FETCH_ASSOC);

    if ($border_key_event) {
        // ユーザーのステータスがイベントのステータスを上回っているか確認
        $is_user_superior = (
            $user_data['technical_skill'] > $border_key_event['event_technical'] &&
            $user_data['negotiation_skill'] > $border_key_event['event_negotiation'] &&
            $user_data['appearance'] > $border_key_event['event_appearance'] &&
            $user_data['popularity'] > $border_key_event['event_popularity']
        );

        if ($is_user_superior) {
            // border_key = 2 のイベントを取得
            $border_key_2_query = "SELECT * FROM Point 
                                   WHERE event_id = :event_id AND border = 1 AND border_key = 2
                                   ORDER BY RAND() LIMIT 1";
            $border_key_2_stmt = $conn->prepare($border_key_2_query);
            $border_key_2_stmt->execute([':event_id' => $event['event_id']]);
            $border_key_2_event = $border_key_2_stmt->fetch(PDO::FETCH_ASSOC);

            $event_to_use = $border_key_2_event;
            $status_update_query = "UPDATE Status 
                                    SET trust_level = LEAST(trust_level + :event_trust + :current_trust, 100),
                                        technical_skill = LEAST(technical_skill + :event_technical + :current_technical, 100),
                                        negotiation_skill = LEAST(negotiation_skill + :event_negotiation + :current_negotiation, 100),
                                        appearance = LEAST(appearance + :event_appearance + :current_appearance, 100),
                                        popularity = LEAST(popularity + :event_popularity + :current_popularity, 100),
                                        total_score = trust_level + technical_skill + negotiation_skill + appearance + popularity
                                    WHERE status_id = :status_id";
    $status_update_stmt = $conn->prepare($status_update_query);
    $status_update_stmt->execute([
                                    ':event_trust' => $event_to_use['event_trust'],
                                    ':event_technical' => $event_to_use['event_technical'],
                                    ':event_negotiation' => $event_to_use['event_negotiation'],
                                    ':event_appearance' => $event_to_use['event_appearance'],
                                    ':event_popularity' => $event_to_use['event_popularity'],
                                    ':current_trust' => $user_data['trust_level'],
                                    ':current_technical' => $user_data['technical_skill'],
                                    ':current_negotiation' => $user_data['negotiation_skill'],
                                    ':current_appearance' => $user_data['appearance'],
                                    ':current_popularity' => $user_data['popularity'],
                                    ':status_id' => $user_data['status_id']
                                ]);
}
    }
} else {
    echo 'ランダムイベントに遷移';
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/border.css">
        <title>ランダムイベント</title>
    </head>
<body>
    <div id="popup" class="popup">
        <h2><p><span class="rotate-text">ステータス</span></p></h2>
        <p><h2><?php echo htmlspecialchars($user_data['role_name']); ?></h2></p>
        <p><h1><?php echo htmlspecialchars($user_data['user_name']); ?></h1></p>
        <p><h3>
            信頼度：<span><?php echo $user_data['trust_level']; ?></span><br>
            技術力：<span><?php echo $user_data['technical_skill']; ?></span><br>
            交渉力：<span><?php echo $user_data['negotiation_skill']; ?></span><br>
            容姿：<span><?php echo $user_data['appearance']; ?></span><br>
            好感度：<span><?php echo $user_data['popularity']; ?></span><br>
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
            <button id="next-term" class="game-button">1年を終える</button>
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