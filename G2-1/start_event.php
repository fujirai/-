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

    // Careerテーブルからcurrent_termとcurrent_monthsを取得
    $career_query = "SELECT current_term, current_months FROM Career WHERE user_id = :user_id";
    $career_stmt = $conn->prepare($career_query);
    $career_stmt->execute([':user_id' => $user_id]);
    $career = $career_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$career) {
        throw new Exception("Career情報が見つかりません。");
    }

    $current_term = $career['current_term'];
    $current_month = $career['current_months'];

    // EventテーブルとPointテーブルを結合してイベントを取得
    $event_query = "SELECT e.*, p.choice, p.border 
        FROM Event e
        LEFT JOIN Point p ON e.event_id = p.event_id
        WHERE e.event_term = :current_term AND e.event_months = :current_month
        ORDER BY RAND() LIMIT 1";
    $event_stmt = $conn->prepare($event_query);
    $event_stmt->execute([
        ':current_term' => $current_term,
        ':current_month' => $current_month
    ]);
    $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo "該当するイベントが見つかりません。";
        exit;
    }

    // イベント情報をセッションに保存
    $_SESSION['event'] = $event;

    // 適切なリダイレクト先を決定
    if (isset($event['choice']) && $event['choice'] == 1) {
        header("Location: ../G3-1/choice.php");
        exit;
    } elseif (isset($event['border']) && $event['border'] == 1) {
        header("Location: ../G3-1/border.php");
        exit;
    } else {
        header("Location: ../G3-1/randamu.php");
        exit;
    }
} catch (PDOException $e) {
    echo "データベースエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
} catch (Exception $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>
