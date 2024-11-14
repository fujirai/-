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

    // Pointテーブルから該当する選択肢を取得
    $point_query = "SELECT choice_key, choice_script FROM Point WHERE event_id = :event_id";
    $point_stmt = $conn->prepare($point_query);
    $point_stmt->execute([':event_id' => $event['event_id']]);
    $choices = $point_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$choices) {
        throw new Exception("選択肢情報が見つかりません。");
    }

    // JSONエンコードしてJavaScriptに渡す
    $choice_details = json_encode($choices, JSON_UNESCAPED_UNICODE);

    // ユーザーデータとステータスを取得
    $query = "SELECT User.user_name, Status.trust_level, Status.technical_skill, 
                     Status.negotiation_skill, Status.appearance, Status.popularity 
              FROM User 
              JOIN Status ON User.status_id = Status.status_id 
              WHERE User.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("ユーザー情報が見つかりません。");
    }

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
    <link rel="stylesheet" href="css/choice.css">
    <title>選択イベント</title>
    <script>
        const choiceDetails = <?php echo $choice_details; ?>;
    </script>
</head>
<body>
    <div id="popup" class="popup">
        <h2><span class="rotate-text">ステータス</span></h2>
        <p><h2><?php echo htmlspecialchars($user['user_name'], ENT_QUOTES, 'UTF-8'); ?></h2></p>
        <p><h3>
            信頼度：<?php echo $user['trust_level']; ?><br>
            技術力：<?php echo $user['technical_skill']; ?><br>
            交渉力：<?php echo $user['negotiation_skill']; ?><br>
            容姿：<?php echo $user['appearance']; ?><br>
            好感度：<?php echo $user['popularity']; ?><br>
        </h3></p>
    </div>

    <div class="fixed-title">
        <h1>イベント:<?php echo htmlspecialchars($event['event_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
    </div>
    <div class="footer-box">
        <h2><?php echo htmlspecialchars($event['event_description'], ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>
    <form method="POST" action="process_choice.php">
        <div class="options">
            <?php foreach ($choices as $choice): ?>
                <button class="option-button" type="submit" name="choice_key" value="<?php echo htmlspecialchars($choice['choice_key'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($choice['choice_script'], ENT_QUOTES, 'UTF-8'); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </form>

    <script>
        var popup = document.getElementById("popup");
        popup.addEventListener("click",function(){
            popup.classList.toggle("show");
        })

        document.addEventListener("DOMContentLoaded", function () {
            const textElement = document.querySelector(".footer-box h2");
            const optionsElement = document.querySelector(".options");
            const text = textElement.textContent;
            textElement.textContent = "";
            let i = 0;

            function type() {
                if (i < text.length) {
                    textElement.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, 25); // 25msごとに1文字ずつ表示
                } else {
                    showOptions(); // テキストが全て表示されたら選択肢を表示
                }
            }
            function showOptions() {
                optionsElement.style.opacity = "1"; // フェードイン表示
            }
            type();
        });

        function updateFooter(option) {
            const footerBox = document.querySelector('.footer-box h2');

            // サーバーから渡されたchoiceDetailsを利用
            const detail = choiceDetails.find(choice => choice.choice_key == option);

            if (detail) {
                footerBox.textContent = detail.choice_detail; // 選択肢の詳細を表示
            } else {
                footerBox.textContent = "不明な選択肢が選ばれました。";
            }

            // ボタン非表示や戻るボタン表示は同じ
            const buttons = document.querySelectorAll('.option-button');
            buttons.forEach(button => button.style.display = 'none');

            setTimeout(() => {
                const modo = document.getElementById("modo");
                modo.style.display = "block";
            }, 1000);

            const backButton = document.getElementById("backButton");
            backButton.addEventListener("click", function () {
                window.location.href = '../G2-1/home.php';
            });
        }
    </script>
</body>
</html>
