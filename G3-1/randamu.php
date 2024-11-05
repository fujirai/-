<?php
session_start();
require_once __DIR__ . '/../db.php';   // DB接続ファイルをインクルード

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

try {
    $conn = connectDB();
    $user_id = $_SESSION['user_id'];

    if (isset($_POST['start_event'])) {
        // Careerテーブルから現在のタームと月を取得
        $career_query = "SELECT current_term, current_months FROM Career WHERE user_id = :user_id";
        $career_stmt = $conn->prepare($career_query);
        $career_stmt->execute([':user_id' => $user_id]);
        $career = $career_stmt->fetch();

        if (!$career) {
            throw new Exception("Careerデータが見つかりません。");
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

        // 4ターム目の3月でゲーム終了処理
if ($new_term > 4 && $new_month == 4) {
    $update_query = "UPDATE User SET game_situation = 'end' WHERE user_id = :user_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->execute([':user_id' => $user_id]);

    echo '<div class="footer-box"><h2>ゲーム終了！お疲れ様でした。</h2></div>';
    echo '<button id="modo" style="display:none;" onclick="location.href=\'term.php\'">次へ</button>';
    exit;
}
        // Careerテーブルのcurrent_termとcurrent_monthsに対応するイベントを取得し、セッションに保存
        $event_query = "SELECT * FROM Event 
                        WHERE event_term = :current_term AND event_months = :current_month 
                        ORDER BY RAND() LIMIT 1";
        $event_stmt = $conn->prepare($event_query);
        $event_stmt->execute([
            ':current_term' => $current_term,
            ':current_month' => $current_month
        ]);
        $event = $event_stmt->fetch();

        if (!$event) {
            throw new Exception("該当するイベントが見つかりません。");
        }

        // イベント情報をセッションに保存
        $_SESSION['event'] = $event;

        // イベントの影響をStatusに反映
        $status_update = "UPDATE Status SET 
                          trust_level = trust_level + :event_trust, 
                          technical_skill = technical_skill + :event_technical, 
                          negotiation_skill = negotiation_skill + :event_negotiation, 
                          apparance = apparance + :event_appearance, 
                          popularity = popularity + :event_popularity 
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
                         SET total_score = trust_level + technical_skill + negotiation_skill + apparance + popularity 
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

    } elseif (isset($_SESSION['event'])) {
        // セッションからイベントを取得して表示
        $event = $_SESSION['event'];
    } else {
        throw new Exception("表示するイベントがありません。");
    }

} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/randamu2.css">
        <title>ランダムイベント</title>
    </head>
<body>
    <div id="popup" class="popup">
        <h2><p><span class="rotate-text">ステータス</span></p></h2>
        <!-- ここにDBからステータスを追加 -->
         <p><h2>平社員</h2></p>
         <p><h1>名前</h1></p>
        <p><h3>
            信頼度：20<br>
            技術力：10<br>
            交渉力：50<br>
            容　姿：50<br>
            好感度：50<br>
        </h3></p>
    </div>
    <div class="fixed-title">
    <h1>イベント: <?php echo htmlspecialchars($event['event_name']); ?></h1>
    </div>
    <div class="footer-box">
        <h2><?php echo htmlspecialchars($event['event_description']); ?></h2>
    </div>
    <div id="modo" class="modo" style="display: none;">
        <button id="backButton" class="game-button">戻る</button>
    </div>
    <div id="nextTermButton" class="nextTermButton" style="display: none;">
        <button id="next-term" class="game-button">次のタームへ</button>
    </div>
    <script>
        var popup = document.getElementById("popup");
        popup.addEventListener("click",function(){
            popup.classList.toggle("show");
        })
        document.addEventListener("DOMContentLoaded", function () {
        const textElement = document.querySelector(".footer-box h2");
        const text = textElement.textContent;
        textElement.textContent = "";
        let i = 0;

        function type() {
            if (i < text.length) {
                textElement.textContent += text.charAt(i);
                i++;
                setTimeout(type, 25); // 25msごとに1文字ずつ表示
            } else {
                // 文字が全て表示された後、次のタームが4月かどうか確認
                checkNextTerm();
            }
        }
        function checkNextTerm() {
            // PHPから取得した新しいタームと月の値を使用して確認
            const newMonth = <?php echo $new_month; ?>;
            const newTerm = <?php echo $new_term; ?>;

            if (newMonth === 4 && newTerm > <?php echo $current_term; ?>) {
                 // 4月で次のタームに移行した場合、「次のタームへ」ボタンを表示
                 setTimeout(() => {
                    const nextTermButton = document.getElementById("nextTermButton");
                    if (nextTermButton) {
                        nextTermButton.style.display = "block";
                        const nextTerm = document.getElementById("next-term");
                        nextTerm.addEventListener("click", () => {
                            window.location.href = 'term.php';
                        });
                    }
                }, 1000);
            } else {
                // それ以外の場合、「戻る」ボタンを表示
                setTimeout(() => {
                    const modo = document.getElementById("modo");
                    if (modo) modo.style.display = "block";
                }, 1000);
            }
        }

        if (textElement) type();

            // 戻るボタンのクリックイベント
            const backButton = document.getElementById("backButton");
            backButton.addEventListener("click", function () {
                window.location.href = '../G2-1/home.php';
            });
        });
    </script>
</body>
</html>