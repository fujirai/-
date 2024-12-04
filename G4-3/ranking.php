<?php
session_start();
require_once '../db.php';

// アクセス元を確認
$source = isset($_GET['from']) ? $_GET['from'] : null;

// セッションが必要な処理（gameend.phpからのアクセス）と不要な処理（index.htmlからのアクセス）で分岐
$userHighlight = null; // 現在のユーザー情報を保持する変数
$userRank = null; // ユーザーの順位を保持する変数

if ($source === 'gameend' && isset($_SESSION['user_id'])) {
    $userHighlight = [
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'],
        'role_name' => $_SESSION['role_name'] ?? '役職なし',
        'total_score' => $_SESSION['trust_level'] + $_SESSION['technical_skill'] +
                         $_SESSION['negotiation_skill'] + $_SESSION['appearance'] +
                         $_SESSION['popularity'],
    ];

    // ユーザーの順位を計算
    foreach ($allRankResults as $index => $row) {
        if ($row['user_id'] == $userHighlight['user_id']) {
            $userRank = $index + 1; // 配列のインデックスは0から始まるため+1
            break;
        }
    }
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
                // ユーザー情報を最上位に表示
                if ($userHighlight) {
                    echo "<tr class='highlight-user'>";
                    echo "<td>" . htmlspecialchars($userRank ?? '-') . "</td>"; // ユーザーの順位を表示
                    echo "<td><i class='fas fa-user avatar'></i> " . htmlspecialchars($userHighlight['user_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($userHighlight['role_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($userHighlight['total_score']) . "</td>";
                    echo "</tr>";
                }

                // 通常のランキングを表示
                $rank = 1; // 順位カウンタ
                foreach ($top10Results as $row) {
                    echo "<tr class='highlight-row'>";
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
                        <th>役職</th>
                        <th>スコア</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($remainingResults as $index => $row) {
                        $actualRank = $index + 11; // 11位からの順位
                        echo "<tr class='highlight-row'>";
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
        const numberOfPieces = 100;

        for (let i = 0; i < numberOfPieces; i++) {
            const confettiPiece = document.createElement('div');
            confettiPiece.classList.add('confetti-piece');
            confettiPiece.style.left = `${Math.random() * 100}vw`;
            confettiPiece.style.backgroundColor = `hsl(${Math.random() * 360}, 100%, 75%)`;
            confettiPiece.style.animationDelay = `${Math.random() * 5}s`;
            confettiContainer.appendChild(confettiPiece);
        }
    </script>
</body>
</html>
