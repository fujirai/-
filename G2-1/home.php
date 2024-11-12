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

    // ユーザーデータとステータスを取得
    $query = "SELECT User.user_name, Status.trust_level, Status.technical_skill, 
                     Status.negotiation_skill, Status.appearance, Status.popularity, 
                     Status.total_score, User.role_id 
              FROM User 
              JOIN Status ON User.status_id = Status.status_id 
              WHERE User.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "ユーザー情報が見つかりません。";
        exit;
    }

    // Roleテーブルから全役職を取得
    $role_query = "SELECT * FROM Role ORDER BY repuired_status DESC";
    $role_stmt = $conn->query($role_query);
    $roles = $role_stmt->fetchAll();

    $new_role_id = $user['role_id'];  // 役職の初期化（デフォルトは現在の役職）

    foreach ($roles as $role) {
        if ($user['total_score'] >= $role['repuired_status'] &&
            $user['trust_level'] >= $role['repuired_trust'] &&
            $user['technical_skill'] >= $role['repuired_technical'] &&
            $user['negotiation_skill'] >= $role['repuired_negotiation'] &&
            $user['appearance'] >= $role['repuired_appearance'] &&
            $user['popularity'] >= $role['repuired_popularity']
        ) {
            $new_role_id = $role['role_id'];
            break;
        }
    }

    // Careerテーブルからcurrent_termとcurrent_monthsを取得
    $career_query = "SELECT current_term, current_months FROM Career WHERE user_id = :user_id";
    $career_stmt = $conn->prepare($career_query);
    $career_stmt->execute([':user_id' => $user_id]);
    $career = $career_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$career) {
        throw new Exception("Career情報が見つかりません。");
    }

    // ユーザーの役職を更新（必要な場合）
    if ($new_role_id != $user['role_id']) {
        $update_role_query = "UPDATE User SET role_id = :new_role_id WHERE user_id = :user_id";
        $update_role_stmt = $conn->prepare($update_role_query);
        $update_role_stmt->execute([
            ':new_role_id' => $new_role_id,
            ':user_id' => $user_id
        ]);
    }

    // 現在の役職を取得
    $current_role_query = "SELECT role_name, role_explanation FROM Role WHERE role_id = :role_id";
    $current_role_stmt = $conn->prepare($current_role_query);
    $current_role_stmt->execute([':role_id' => $new_role_id]);
    $current_role = $current_role_stmt->fetch(PDO::FETCH_ASSOC);

    // イベント開始ボタンが押された場合のみイベント判定を実行
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_event'])) {
        $current_term = $career['current_term'];
        $current_month = $career['current_months'];

        // Eventテーブルから該当するイベントを取得
        $event_query = "SELECT * FROM Event 
                        WHERE event_term = :current_term AND event_months = :current_month 
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
        $_SESSION['point'] = $point;

        // イベント処理後、ゲーム終了条件のチェック
        // if ($current_term == 4 && $current_month == 3) {
        //     // ゲーム終了へ遷移
        //     header("Location: ../G4-1/ending.php");
        //     exit;
        // }

        // イベントタイプに応じたページにリダイレクト
        if ($point['choice'] == '1' && is_null($point['border'])) {
            header("Location: ../G3-1/choice.php");
            exit;
        } elseif (is_null($point['choice']) && $point['border'] == '1') {
            header("Location: ../G3-1/border.php");
            exit;
        } else {
            header("Location: ../G3-1/randamu.php");
            exit;
        }
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
    <title>ゲーム画面</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">
        <div class="top-section">
            <div id="openModal" class="status-icon">
                <img src="grapt.jpg" alt="ステータスアイコン">
            </div>

            <div class="year">
                <?php echo $career['current_term']; ?>年目の<?php echo $career['current_months']; ?>月
            </div>
            <div class="name"><?php echo htmlspecialchars($user['user_name']); ?></div>
            <hr class="divider">
            <div class="role"><?php echo htmlspecialchars($current_role['role_name']); ?></div>
        </div>

        <div class="stats-character">
            <div class="stats">
                <div class="stat" data-stat="trust">信頼度：<span><?php echo $user['trust_level']; ?></span></div>
                <div class="stat" data-stat="tech">技術力：<span><?php echo $user['technical_skill']; ?></span></div>
                <div class="stat" data-stat="negotiation">交渉力：<span><?php echo $user['negotiation_skill']; ?></span></div>
                <div class="stat" data-stat="appearance">容　姿：<span><?php echo $user['appearance']; ?></span></div>
                <div class="stat" data-stat="likability">好感度：<span><?php echo $user['popularity']; ?></span></div>
            </div>

            <div class="character">
                <img src="character.png" alt="キャラクター">
            </div>
        </div>

        <div class="buttons">
            <div class="button-wrapper">
                <button onclick="location.href='../G1-0/index.html'">タイトルへ</button>
                <hr class="button-divider">
                <form action="../G3-1/randamu.php" method="POST">
                    <button type="submit" name="start_event">イベント開始</button>
                </form>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <canvas id="radarChart" style="width:500px; height: 500px;"></canvas>
        </div>
    </div>

    <script>
        const modal = document.getElementById('myModal');
        const btn = document.getElementById('openModal');
        const span = document.getElementsByClassName('close')[0];

        btn.onclick = function () {
            modal.style.display = 'block';
            loadRadarChart();
        };

        span.onclick = function () {
            modal.style.display = 'none';
        };

        window.onclick = function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };

        function getStatusValues() {
            const stats = document.querySelectorAll('.stat span');
            return Array.from(stats).map(stat => parseInt(stat.textContent, 10));
        }

        function loadRadarChart() {
            const dataValues = getStatusValues();

            const ctx = document.getElementById('radarChart').getContext('2d');
            if (radarChart) {
                radarChart.destroy();
            }
            radarChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['信頼度', '技術力', '交渉力', '容姿', '好感度'],
                    datasets: [{
                        label: 'ステータス',
                        data: dataValues,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 3,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            min: 0,
                            max: 100,
                            ticks: {
                                beginAtZero: true,
                                stepSize: 10,
                                backdropColor: 'transparent',
                                font: { 
                                    size: 14, 
                                    weight: 'bold' 
                                },
                                color: '#333'
                            },
                            grid: {
                                circular: false,
                                lineWidth: 2,
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            angleLines: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.2)',
                                lineWidth: 1.5
                            },
                            pointLabels: {
                                font: {
                                    size: 17,
                                    weight: 'bold'
                                },
                                color: '#000'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                font: {
                                    size: 15,
                                    weight: 'bold'
                                },
                                color: '#333'
                            }
                        }
                    }
                }
            });
        }

        let radarChart;
    </script>
</body>
</html>
