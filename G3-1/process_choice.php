<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['event'])) {
    header("Location: ../G1-0/index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice_key'])) {
    try {
        $conn = connectDB();
        $user_id = $_SESSION['user_id'];
        $choice_key = (int) $_POST['choice_key'];

        // セッションからイベント情報を取得
        $event = $_SESSION['event'];

        // 選択肢データを取得
        $point_query = "SELECT event_trust, event_technical, event_negotiation, 
                               event_appearance, event_popularity, choice_detail
                        FROM Point 
                        WHERE event_id = :event_id AND choice_key = :choice_key";
        $point_stmt = $conn->prepare($point_query);
        $point_stmt->execute([
            ':event_id' => $event['event_id'],
            ':choice_key' => $choice_key
        ]);
        $point = $point_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$point) {
            throw new Exception("選択肢のデータが見つかりません。");
        }

        // // Careerテーブルから現在のタームと月を取得
        // $career_query = "SELECT current_term, current_months FROM Career WHERE user_id = :user_id";
        // $career_stmt = $conn->prepare($career_query);
        // $career_stmt->execute([':user_id' => $user_id]);
        // $career = $career_stmt->fetch(PDO::FETCH_ASSOC);

        // if (!$career) {
        //     throw new Exception("Career情報が見つかりません。");
        // }

        // $current_term = $career['current_term'];
        // $current_month = $career['current_months'];

        // // 月の進行とタームの進行を分けて管理
        // if ($current_month == 12) {  // 12月が終わった場合
        //     $new_month = 1;  // 1月に戻る
        // } else {
        //     $new_month = $current_month + 1;  // 次の月へ進む
        // }

        // // タームの進行を判定
        // if ($new_month == 4) {  // 4月になった場合
        //     $new_term = $current_term + 1;  // 新しいタームに進む
        // } else {
        //     $new_term = $current_term;
        // }

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
            ':event_trust' => $point['event_trust'],
            ':event_technical' => $point['event_technical'],
            ':event_negotiation' => $point['event_negotiation'],
            ':event_appearance' => $point['event_appearance'],
            ':event_popularity' => $point['event_popularity'],
            ':user_id' => $user_id
        ]);

        // total_scoreの更新
        $score_update = "UPDATE Status 
                         SET total_score = trust_level + technical_skill + negotiation_skill + appearance + popularity 
                         WHERE status_id = (SELECT status_id FROM User WHERE user_id = :user_id)";
        $score_stmt = $conn->prepare($score_update);
        $score_stmt->execute([':user_id' => $user_id]);

        // // Careerテーブルを更新する
        // $update_career_query = "UPDATE Career SET current_term = :new_term, current_months = :new_month WHERE user_id = :user_id";
        // $update_career_stmt = $conn->prepare($update_career_query);
        // $update_career_stmt->execute([
        //     ':new_term' => $new_term,
        //     ':new_month' => $new_month,
        //     ':user_id' => $user_id
        // ]);

        // 選択肢の詳細をセッションに保存
        $_SESSION['choice_detail'] = $point['choice_detail'];

        // choice.php にリダイレクト
        header("Location: choice.php");
        exit;

    } catch (PDOException $e) {
        echo "データベースエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    } catch (Exception $e) {
        echo "エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
}
?>
