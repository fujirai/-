<?php
session_start();
require_once 'db.php';  // DB接続ファイルをインクルード

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

        // 4ターム目の3月でゲーム終了
        if ($new_term > 4 && $new_month == 4) {
            $update_query = "UPDATE User SET game_situation = 'end' WHERE user_id = :user_id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([':user_id' => $user_id]);
            echo "<h1>ゲーム終了！お疲れ様でした。</h1>";
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

        // home.php にリダイレクト
        header("Location: event.php");
        exit;

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
    <title>イベント詳細</title>
</head>
<body>
    <h1>イベント: <?php echo htmlspecialchars($event['event_name']); ?></h1>
    <p><?php echo htmlspecialchars($event['event_description']); ?></p>

    <form action="home.php" method="POST">
        <button type="submit">ホーム画面に戻る</button>
    </form>
</body>
</html>
