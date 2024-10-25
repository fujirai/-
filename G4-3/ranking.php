<?php
require_once '../db.php'; // 上記のコードをdb_config.phpとして保存したと仮定

try {
    // データベースに接続
    $conn = connectDB();

    // SQLクエリでユーザー名とスコアを取得
    $sql = "SELECT User.user_name, Status.total_score 
            FROM User 
            INNER JOIN Status ON User.status_id = Status.status_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
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

        <table class="ranking-table">
            <thead>
                <tr>
                    <th>順位</th>
                    <th>名前</th>
                    <th>スコア</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $rank = 1; // 順位カウンタ
                foreach ($results as $row) {
                    echo "<tr class='highlight-row'>";
                    echo "<td>" . $rank++ . "</td>";
                    echo "<td><i class='fas fa-user avatar'></i> " . htmlspecialchars($row['user_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['total_score']) . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
?>
        <div class="buttons-container">
            <button class="btn-ranking" onclick="goToHomePage()">会社HPへ</button>
        </div>        
    </div>

    <div class="confetti"></div> <!-- クラッカーのエフェクト -->

    <script>
        function goToHomePage() {
            window.location.href = '../G1-0/index.html';
        }

        // クラッカーのエフェクトを生成
        const confettiContainer = document.querySelector('.confetti');
        const numberOfPieces = 100; // クラッカーの数

        for (let i = 0; i < numberOfPieces; i++) {
            const confettiPiece = document.createElement('div');
            confettiPiece.classList.add('confetti-piece');
            confettiPiece.style.left = `${Math.random() * 100}vw`;
            confettiPiece.style.backgroundColor = `hsl(${Math.random() * 360}, 100%, 75%)`;
            confettiPiece.style.animationDelay = `${Math.random() * 5}s`;
            confettiContainer.appendChild(confettiPiece);
        }
    </script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="./js/animation.js"></script>
</body>
</html>
