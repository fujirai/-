<?php
session_start();
require_once __DIR__ . '/../db.php'; // DB接続ファイルをインクルード

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ログイン情報が見つかりません。']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '無効なリクエスト方法です。']);
    exit;
}

try {
    $conn = connectDB();
    $user_id = $_SESSION['user_id'];

    // リクエストのbodyを取得してJSONデコード
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'アクションが指定されていません。']);
        exit;
    }

    $action = $data['action'];

    // Careerテーブルから現在のタームと月を取得
    $career_query = "SELECT current_term, current_months FROM Career WHERE user_id = :user_id";
    $career_stmt = $conn->prepare($career_query);
    $career_stmt->execute([':user_id' => $user_id]);
    $career = $career_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$career) {
        echo json_encode(['success' => false, 'message' => 'Career情報が見つかりません。']);
        exit;
    }

    $current_term = (int)$career['current_term'];
    $current_months = (int)$career['current_months'];

    // // アクションに応じて更新
    // if ($action === 'next_term') {
    //     $current_term += 1;
    //     $current_months = 1; // 新しいタームが始まるので月をリセット
    // } elseif ($action === 'back') {
    //     $current_months += 1; // 月を進める
    //     if ($current_months > 3) { // 3ヶ月を超えたら次のタームに進む
    //         $current_months = 1;
    //         $current_term += 1;
    //     }
    // } elseif ($action === 'ending') {
    //     $current_term = 4;
    //     $current_months = 3; // エンディング用に固定
    // } else {
    //     echo json_encode(['success' => false, 'message' => '無効なアクションです。']);
    //     exit;
    // }

    // アクションに基づいて条件を適用
    if ($action === 'next_term') {
        if ($current_term === 4 && $current_months === 3) {
            // 更新しない（エンディング条件）
            echo json_encode(['success' => false, 'message' => '最終タームに達しており更新不要です。']);
            exit;
        } else {
            $current_term += 1; // タームを進める
            $current_months += 1; // 月も進める
        }
    } elseif ($action === 'back') {
        if ($current_term === 4 && $current_months === 3) {
            // 更新しない（エンディング条件）
            echo json_encode(['success' => false, 'message' => '最終タームに達しており更新不要です。']);
            exit;
        } else {
            $current_months += 1; // 月を進める
            if ($current_months > 12) { // 月が12を超えた場合リセット
                $current_months = 1;
            }
        }
    } elseif ($action === 'ending') {
        if ($current_term === 4 && $current_months === 3) {
            // エンディング状態なので更新不要
            echo json_encode(['success' => true, 'message' => 'エンディング状態です。']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'エンディング条件を満たしていません。']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => '無効なアクションです。']);
        exit;
    }

    // Careerテーブルを更新
    $update_query = "UPDATE Career SET current_term = :current_term, current_months = :current_months WHERE user_id = :user_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->execute([
        ':current_term' => $current_term,
        ':current_months' => $current_months,
        ':user_id' => $user_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Careerが正常に更新されました。']);
} catch (PDOException $e) {
    error_log("データベースエラー: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'データベースエラーが発生しました。']);
} catch (Exception $e) {
    error_log("エラー: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'エラーが発生しました。']);
}
