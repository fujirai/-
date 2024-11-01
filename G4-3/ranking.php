<?php
session_start();
require_once '../db.php';

// アクセス元を確認
$source = isset($_GET['from']) ? $_GET['from'] : null;

// セッションが必要な処理（gameend.phpからのアクセス）と不要な処理（index.htmlからのアクセス）で分岐
if ($source === 'gameend') {
    if (!isset($_SESSION['user_id'])) {
        echo "エラー: ユーザー情報がありません。ログインしてから再度アクセスしてください。";
        exit;
    }
    $userID = $_SESSION['user_id'];
} else {
    // セッションが不要な場合
    $userID = null;
}

try {
    // データベースに接続
    $conn = connectDB();

    // 全ユーザーのスコアと役職を降順で取得し、game_situationが'end'のユーザーをフィルタリング
    $sqlAllRanks = "
        SELECT 
            User.user_name, 
            User.game_situation, 
            Status.total_score, 
            User.user_id,
            Role.role_name 
        FROM User 
        INNER JOIN Status ON User.status_id = Status.status_id
        LEFT JOIN Role ON User.role_id = Role.role_id
        WHERE User.game_situation = 'end'
        ORDER BY Status.total_score DESC";
        
    $stmtAllRanks = $conn->prepare($sqlAllRanks);
    $stmtAllRanks->execute();
    $allRankResults = $stmtAllRanks->fetchAll();

    // 上位10位と11位以降に分割
    $top10Results = array_slice($allRankResults, 0, 10); // 上位10件
    $remainingResults = array_slice($allRankResults, 10); // 11位以降
} catch (PDOException $e) {
    echo "接続エラー: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelCompany - 社長ランキング</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Plus+Jakarta+Sans:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div id="particles-js"></div> <!-- パーティクル背景 -->

    <div class="ranking-wrapper">
        <div class="ranking-header">
            <i class="fas fa-crown crown-icon"></i>
            <h1>Company Ranking</h1>
        </div>        

        <table class="ranking-table" id="rankingTable">
            <thead>
                <tr>
                    <th>順位</th>
                    <th>名前</th>
                    <th>役職</th>
                    <th>スコア</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rank = 1; // 順位カウンタ
                foreach ($top10Results as $row) {
                    $highlightClass = ($userID && $userID === $row['user_id']) ? 'highlight-user' : 'highlight-row';
                    echo "<tr class='{$highlightClass}'>";
                    echo "<td>" . $rank++ . "</td>";
                    echo "<td><i class='fas fa-user avatar'></i> " . htmlspecialchars($row['user_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['role_name'] ?? '役職なし') . "</td>";
                    echo "<td>" . htmlspecialchars($row['total_score']) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <button class="btn-ranking" id="toggleButton" onclick="toggleRemaining()">11位以降を表示</button>

        <div id="remainingTable" style="display: none;">
            <table class="ranking-table">
                <thead>
                    <tr>
                        <th>順位</th>
                        <th>名前</th>
                        <th>スコア</th>
                        <th>役職</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($remainingResults as $index => $row) {
                        $actualRank = $index + 11; // 11位からの順位
                        $highlightClass = ($userID && $userID === $row['user_id']) ? 'highlight-user' : 'highlight-row';
                        echo "<tr class='{$highlightClass}'>";
                        echo "<td>" . $actualRank . "</td>";
                        echo "<td><i class='fas fa-user avatar'></i> " . htmlspecialchars($row['user_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['role_name'] ?? '役職なし') . "</td>";
                        echo "<td>" . htmlspecialchars($row['total_score']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="buttons-container">
            <button class="btn-ranking" onclick="goToHomePage()">会社HPへ</button>
        </div>        
    </div>

    <div class="confetti"></div> <!-- クラッカーのエフェクト -->

    <script>
        function goToHomePage() {
            window.location.href = '../G1-0/index.html';
        }

        function toggleRemaining() {
            const remainingTable = document.getElementById('remainingTable');
            const toggleButton = document.getElementById('toggleButton');

            if (remainingTable.style.display === 'none') {
                remainingTable.style.display = 'block';
                toggleButton.textContent = '閉じる';
            } else {
                remainingTable.style.display = 'none';
                toggleButton.textContent = '11位以降を表示';
            }
        }
 // クラッカーのエフェクトを生成
const confettiContainer = document.querySelector('.confetti');
const numberOfPieces = 100; // クラッカーの数

for (let i = 0; i < numberOfPieces; i++) {
    const confettiPiece = document.createElement('div');
    confettiPiece.classList.add('confetti-piece');
    confettiPiece.style.left = `${Math.random() * 100}vw`; // 修正: バッククォートを使用
    confettiPiece.style.backgroundColor = `hsl(${Math.random() * 360}, 100%, 75%)`; // 修正: バッククォートを使用
    confettiPiece.style.animationDelay = `${Math.random() * 5}s`; // 修正: バッククォートを使用
    confettiContainer.appendChild(confettiPiece);
}
    </script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>
