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

    $choice_detail = isset($_SESSION['choice_detail']) ? $_SESSION['choice_detail'] : null;
    unset($_SESSION['choice_detail']); // 1度だけ表示するために削除

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
    error_log("データベースエラー: " . $e->getMessage());
    echo "エラーが発生しました。もう一度お試しください。";
    exit;
} catch (Exception $e) {
    error_log("エラー: " . $e->getMessage());
    echo "エラーが発生しました。もう一度お試しください。";
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
            <!-- イベント説明 -->
            <h2 id="description" style="display: <?php echo isset($choice_detail) ? 'none' : 'block'; ?>;">
                <?php echo htmlspecialchars($event['event_description'], ENT_QUOTES, 'UTF-8'); ?>
            </h2>

            <!-- 選択肢詳細（選択後の結果表示） -->
            <h2 id="choice-detail" style="display: <?php echo isset($choice_detail) ? 'block' : 'none'; ?>;">
                <?php 
                if (isset($choice_detail)) {
                    echo htmlspecialchars($choice_detail, ENT_QUOTES, 'UTF-8');
                    unset($_SESSION['choice_detail']); // 1回表示後に削除
                } 
                ?>
            </h2>

            <!-- 選択肢ボタン（選択後は非表示） -->
            <form method="POST" action="process_choice.php" id="choiceForm" style="display: <?php echo isset($choice_detail) ? 'none' : 'block'; ?>;">
                <div class="options">
                    <?php foreach ($choices as $choice): ?>
                        <button class="option-button" type="submit" name="choice_key" value="<?php echo htmlspecialchars($choice['choice_key'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($choice['choice_script'], ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

    <!-- 戻るボタンの表示ロジック -->
    <div id="modo" class="modo" style="display: <?php echo isset($choice_detail) ? 'block' : 'none'; ?>;">
        <?php if ($current_term == 4 && $current_month == 3): ?>
            <button id="endingButton" class="game-button" onclick="updateCareer('ending', '../G4-1/ending.php');">
                <?php echo "エンディングへ"; ?>
            </button>
        <?php elseif ($current_term != 4 && $current_month == 3): ?>
            <button id="nextYearButton" class="game-button" onclick="updateCareer('next_term', 'term.php');">
                <?php echo "1年を終える"; ?>
            </button>
        <?php else: ?>
            <button id="backButton" class="game-button" onclick="updateCareer('back', '../G2-1/home.php');">
                <?php echo "戻る"; ?>
            </button>
        <?php endif; ?>
    </div>

    <script>
        var popup = document.getElementById("popup");
        popup.addEventListener("click", function () {
            popup.classList.toggle("show");
        });

        document.addEventListener("DOMContentLoaded", function () {
            const detailElement = document.querySelector("#choice-detail");
            const modoElement = document.querySelector("#modo");
            const optionsElement = document.querySelector(".options");
            const descriptionElement = document.querySelector("#description");
            const backButton = document.querySelector("#backButton");

            const choiceDescriptionText = descriptionElement.textContent.trim();
            const choiceDetailText = detailElement.textContent.trim();

            // 初期状態では戻るボタンを非表示
            modoElement.style.display = "none";

            // イベント説明を1文字ずつ表示
            function typeText(element, text, callback) {
                element.textContent = "";
                let i = 0;
                function type() {
                    if (i < text.length) {
                        element.textContent += text.charAt(i);
                        i++;
                        setTimeout(type, 25); // 25msごとに文字を追加
                    } else if (callback) {
                        callback(); // テキストが全て表示されたらコールバックを呼び出す
                    }
                }
                type();
            }

            // ボタンのテキストを1文字ずつ表示
            function typeButtonText(buttonId, text) {
                const button = document.getElementById(buttonId);
                if (button) {
                    button.textContent = "";
                    let i = 0;
                    function type() {
                        if (i < text.length) {
                            button.textContent += text.charAt(i);
                            i++;
                            setTimeout(type, 50); // 50msごとに文字を追加
                        }
                    }
                    type();
                }
            }

            // choice_descriptionを表示
            if (choiceDescriptionText && !choiceDetailText) {
                typeText(descriptionElement, choiceDescriptionText, () => {
                    document.querySelector(".options").style.opacity = "1"; // 選択肢を表示
                });
            }

            // リダイレクト後の処理
            if (choiceDetailText) {
                descriptionElement.style.display = "none"; // イベント説明を非表示
                optionsElement.style.display = "none"; // 選択肢を非表示
                typeText(detailElement, choiceDetailText, () => {
                    modoElement.style.display = "block"; // 戻るボタンを表示

                    // 条件によるボタン表示
                    const currentTerm = parseInt("<?php echo $current_term; ?>", 10);
                    const currentMonth = parseInt("<?php echo $current_month; ?>", 10);

                    if (currentTerm === 4 && currentMonth === 3) {
                        typeButtonText("endingButton", "エンディングへ");
                    } else if (currentTerm !== 4 && currentMonth === 3) {
                        typeButtonText("nextYearButton", "1年を終える");
                    } else {
                        typeButtonText("backButton", "戻る");
                    }
                });
            }

            // 戻るボタンのクリックイベント
            if (backButton) {
                backButton.addEventListener("click", function () {
                    window.location.href = "../G2-1/home.php";
                });
            }

            // 選択肢ボタンのクリックイベント
            const buttons = document.querySelectorAll(".option-button");
            buttons.forEach(button => {
                button.addEventListener("click", function () {
                    const choiceKey = this.getAttribute("data-choice-key");

                    // 選択肢を非表示
                    optionsElement.style.display = "none";
                    descriptionElement.style.display = "none";

                    // 選択した選択肢の処理をサーバー側に送信
                    const form = document.createElement("form");
                    form.method = "POST";
                    form.action = "process_choice.php";

                    const input = document.createElement("input");
                    input.type = "hidden";
                    input.name = "choice_key";
                    input.value = choiceKey;

                    form.appendChild(input);
                    document.body.appendChild(form);

                    form.submit(); // サーバーへ送信
                });
            });

            window.updateCareer = function(action, redirectUrl) {
                // サーバーにリクエストを送信
                fetch("update_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: action })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Career updated successfully.");
                        // サーバー側の更新が成功したらリダイレクト
                        window.location.href = redirectUrl;
                    } else {
                        console.error("Error updating career:", data.message);
                        alert("更新に失敗しました。もう一度お試しください。");
                    }
                })
                .catch(error => {
                    console.error("Fetch error:", error);
                    alert("エラーが発生しました。");
                });
            };
        });
    </script>
</body>
</html>
