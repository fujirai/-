<?php
session_start();
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];

    try {
        $conn = connectDB();

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

        if ($action === 'next_term') {
            // 次のタームに進む処理
            if ($current_month == 12) {  // 12月の処理
                $new_month = 1;
                $new_term = $current_term + 1;
            } else {
                $new_month = $current_month + 1;
                $new_term = $current_term;
            }
        } elseif ($action === 'ending') {
            // エンディング処理（更新なし）
            $new_month = $current_month;
            $new_term = $current_term;
        } else {
            throw new Exception('無効なアクションです。');
        }

        // Careerテーブルを更新する
        $update_query = "UPDATE Career SET current_term = :new_term, current_months = :new_month WHERE user_id = :user_id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([
            ':new_term' => $new_term,
            ':new_month' => $new_month,
            ':user_id' => $user_id
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
