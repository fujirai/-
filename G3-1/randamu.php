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

    // セッションからイベント情報を取得
    if (!isset($_SESSION['event'])) {
        throw new Exception("イベント情報が見つかりません。");
    }
    $event = $_SESSION['event'];
    $event_id = $event['event_id'];

    // イベントポイント取得処理
    $event_query = "SELECT event_trust, event_technical, event_negotiation,
                    event_appearance, event_popularity
                    FROM Point p
                    WHERE p.event_id = :event_id";
    $event_stmt = $conn->prepare($event_query);
    $event_stmt->execute([':event_id' => $event_id]);

    // 結果を取得
    $event_status = $event_stmt->fetch(PDO::FETCH_ASSOC);


    // ユーザーデータとステータスを取得
    $query = "SELECT User.user_name, Status.trust_level, Status.technical_skill, 
                     Status.negotiation_skill, Status.appearance, Status.popularity 
              FROM User 
              JOIN Status ON User.status_id = Status.status_id 
              WHERE User.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <div class="scene-image">
        <img src="..\Image\<?= htmlspecialchars($event['event_scene']) ?>" loading="eager" alt="背景" class="scene-img">
    </div>
    <!-- ステータス表示 -->
    <div id="popup" class="popup">
        <h2><p><span class="rotate-text">ステータス</span></p></h2>
        <p><h1><?php echo htmlspecialchars($user['user_name']); ?></h1></p>
        <p>
        <h3>
            信頼度：<span><?php echo $user['trust_level']; ?></span>
            <?php
            if($event_status['event_trust']>0){
                echo '<span style = "color : #b22222;">▲</span>';
                echo '<span style = "color : #b22222;">',$event_status['event_trust'],'</span>','<br>';
            }
            else if($event_status['event_trust']==0){
                echo '▲';
                echo $event_status['event_trust'],'<br>';
            }
            else{
                echo '<span style = "color : #4682b4;">▼</span>';
                echo '<span style = "color : #4682b4;">',$event_status['event_trust'],'</span>','<br>';
            }
            ?>

            技術力：<span><?php echo $user['technical_skill']; ?></span>
            <?php
            if($event_status['event_technical']>0){
                echo '<span style = "color : #b22222;">▲</span>';
                echo '<span style = "color : #b22222;">',$event_status['event_technical'],'</span>','<br>';
            }
            else if($event_status['event_technical']==0){
                echo '▲';
                echo $event_status['event_technical'],'<br>';
            }
            else{
                echo '<span style = "color : #4682b4;">▼</span>';
                echo '<span style = "color : #4682b4;">',$event_status['event_technical'],'</span>','<br>';
            }
            ?>

            交渉力：<span><?php echo $user['negotiation_skill']; ?></span>
            <?php
            if($event_status['event_negotiation']>0){
                echo '<span style = "color : #b22222;">▲</span>';
                echo '<span style = "color : #b22222;">',$event_status['event_negotiation'],'</span>','<br>';
            }
            else if($event_status['event_negotiation']==0){
                echo '▲';
                echo $event_status['event_negotiation'],'<br>';
            }
            else{
                echo '<span style = "color : #4682b4;">▼</span>';
                echo '<span style = "color : #4682b4;">',$event_status['event_negotiation'],'</span>','<br>';
            }
            ?>

            容姿：<span><?php echo $user['appearance']; ?></span>
            <?php
            if($event_status['event_appearance']>0){
                echo '<span style = "color : #b22222;">▲</span>';
                echo '<span style = "color : #b22222;">',$event_status['event_appearance'],'</span>','<br>';
            }
            else if($event_status['event_appearance']==0){
                echo '▲';
                echo $event_status['event_appearance'],'<br>';
            }
            else{
                echo '<span style = "color : #4682b4;">▼</span>';
                echo '<span style = "color : #4682b4;">',$event_status['event_appearance'],'</span>','<br>';
            }
            ?>

            好感度：<span><?php echo $user['popularity']; ?></span>
            <?php
            if($event_status['event_popularity']>0){
                echo '<span style = "color : #b22222;">▲</span>';
                echo '<span style = "color : #b22222;">',$event_status['event_popularity'],'</span>','<br>';
            }
            else if($event_status['event_popularity']==0){
                echo '▲';
                echo $event_status['event_popularity'],'<br>';
            }
            else{
                echo '<span style = "color : #4682b4;">▼</span>';
                echo '<span style = "color : #4682b4;">',$event_status['event_popularity'],'</span>','<br>';
            }
            ?>
        </h3>
        </p>
    </div>
    <div class="fixed-title">
            <h1>イベント:<?php echo htmlspecialchars($event['event_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
    <div class="footer-box">
        <h2>
            <?php echo htmlspecialchars($event['event_description'], ENT_QUOTES, 'UTF-8'); ?>
        </h2>
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
            <button id="next-term" class="game-button" >1年間を終える</button>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey && e.key === 'r') || e.key === 'F5') {
                e.preventDefault();
                    // メッセージを表示する要素を作成
                    let message = document.createElement('div');
                    message.style.position = 'fixed';
                    message.style.top = '50%';
                    message.style.left = '50%';
                    message.style.transform = 'translate(-50%, -50%)';
                    message.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
                    message.style.color = 'white';
                    message.style.padding = '20px';
                    message.style.borderRadius = '5px';
                    message.style.fontSize = '20px';
                    message.innerText = 'リロードはできません';

                    // メッセージをページに追加
                    document.body.appendChild(message);

                    // 数秒後にメッセージを非表示にする
                    setTimeout(function() {
                        message.style.display = 'none';
                    }, 1000);

            }
        });
        document.addEventListener("DOMContentLoaded", function () {
            // ポップアップ表示/非表示機能
            const popup = document.getElementById("popup");
            if (popup) {
                popup.addEventListener("click", function () {
                    popup.classList.toggle("show");
                });
            }

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
                    // random_disposal.php に処理を行い、その後 home.php にリダイレクトする
                    window.location.href = "random_disposal.php";  // random_disposal.phpにリダイレクト
                });
            }

            // エンディングボタンの動作
            const endButton = document.getElementById("endButton");
            if (endButton) {
                endButton.addEventListener("click", function () {
                    window.location.href = "random_disposal.php";
                });
            }

            // 次のタームへ進むボタンの動作
            const nextTermButton = document.getElementById("next-term");
            if (nextTermButton) {
                nextTermButton.addEventListener("click", function () {
                    window.location.href = "random_disposal.php";
                });
            }
        });
    </script>
</body>
</html>
