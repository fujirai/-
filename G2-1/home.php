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
    $previous_role_id = null;        // 1つ前の役職を格納

    foreach ($roles as $role) {
        if ($user['total_score'] >= $role['repuired_status'] &&
            $user['trust_level'] >= $role['repuired_trust'] &&
            $user['technical_skill'] >= $role['repuired_technical'] &&
            $user['negotiation_skill'] >= $role['repuired_negotiation'] &&
            $user['appearance'] >= $role['repuired_appearance'] &&
            $user['popularity'] >= $role['repuired_popularity']
        ) {
            $previous_role_id = $new_role_id;  // 現在の役職を1つ前の役職として保存
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
        $previous_role_name = null;
        if ($previous_role_id) {
            $previous_role_query = "SELECT role_name FROM Role WHERE role_id = :role_id";
            $previous_role_stmt = $conn->prepare($previous_role_query);
            $previous_role_stmt->execute([':role_id' => $previous_role_id]);
            $previous_role = $previous_role_stmt->fetch(PDO::FETCH_ASSOC);
            $previous_role_name = $previous_role['role_name'] ?? null;
        }

        // Historyテーブルを更新（1つ前の役職名を使用）
        $history_update_query = "
            UPDATE History 
            SET history_role = :history_role, 
                history_term = :history_term, 
                history_month = :history_month 
            WHERE user_id = :user_id";
        $history_update_stmt = $conn->prepare($history_update_query);
        $history_update_stmt->execute([
            ':history_role' => $previous_role_name,       // 1つ前の役職名を保存
            ':history_term' => $career['current_term'],   // 現在のターム
            ':history_month' => $career['current_months'], // 現在の月
            ':user_id' => $user_id
        ]);
        
        $update_role_query = "UPDATE User SET role_id = :new_role_id WHERE user_id = :user_id";
        $update_role_stmt = $conn->prepare($update_role_query);
        $update_role_stmt->execute([
            ':new_role_id' => $new_role_id,
            ':user_id' => $user_id
        ]);
    }

    // 現在の役職を取得
    $current_role_query = "SELECT role_name, role_explanation, office_png FROM Role WHERE role_id = :role_id";
    $current_role_stmt = $conn->prepare($current_role_query);
    $current_role_stmt->execute([':role_id' => $new_role_id]);
    $current_role = $current_role_stmt->fetch(PDO::FETCH_ASSOC);

    // // イベント開始ボタンが押された場合のみイベント判定を実行
    // if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_event'])) {
    //     $current_term = $career['current_term'];
    //     $current_month = $career['current_months'];

    //     // Eventテーブルから該当するイベントを取得
    //     $event_query = "SELECT * FROM Event 
    //                     WHERE event_term = :current_term AND event_months = :current_month 
    //                     ORDER BY RAND() LIMIT 1";
    //     $event_stmt = $conn->prepare($event_query);
    //     $event_stmt->execute([
    //         ':current_term' => $current_term,
    //         ':current_month' => $current_month
    //     ]);
    //     $event = $event_stmt->fetch(PDO::FETCH_ASSOC);

    //     if (!$event) {
    //         echo "該当するイベントが見つかりません。";
    //         exit;
    //     }

    //     // イベント情報をセッションに保存
    //     $_SESSION['event'] = $event;

    //     // イベントタイプに応じたページにリダイレクト
    //     if ($event['choice'] == 1 && is_null($event['border'])) {
    //         header("Location: ../G3-1/choice.php");
    //         exit;
    //     } elseif (is_null($event['choice']) && $event['border'] == 1) {
    //         header("Location: ../G3-1/border.php");
    //         exit;
    //     } else {
    //         header("Location: ../G3-1/randamu.php");
    //         exit;
    //     }
    // }

    // デフォルト値を設定
$redirect_url = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_event'])) {
    $current_term = $career['current_term'];
    $current_month = $career['current_months'];

    // EventテーブルとPointテーブルを結合してイベントを取得
    $event_query = "SELECT e.*, p.choice, p.border 
        FROM Event e
        LEFT JOIN Point p ON e.event_id = p.event_id
        WHERE e.event_term = :current_term AND e.event_months = :current_month
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

    // 適切なリダイレクト先を決定
    if (isset($event['choice']) && $event['choice'] == 1) {
        $redirect_url = "../G3-1/choice.php";
    } elseif (isset($event['border']) && $event['border'] == 1) {
        $redirect_url = "../G3-1/border.php";
    } else {
        $redirect_url = "../G3-1/randamu.php";
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
    <link rel="stylesheet" href="css/home.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="card-container">
        <div class="card">
            <div class="front">
                        <!-- ボタン -->
                        <button id="openPopupBtn">ポップアップを表示</button>

                <!-- オーバーレイ -->
                <div class="overlay" id="overlay"></div>

                <!-- ポップアップ -->
                <div class="popup" id="popup">
                    <span class="close-btn" id="closeBtn">&times;</span>
                    <p>これはポップアップの内容です。</p>
                </div>
                <div class="company">
                    <img src="..\Image\logo.svg" alt="logo" class="logoimg">
                    <p class="companyname">Hell Company</p>
                </div>
                <div class="contener">
                    <div class="character">
                        <img src="..\Image\<?= htmlspecialchars($current_role['office_png']) ?>" alt="キャラクター" class="character-img">
                    </div>
                    <div class="status">
                        <div class="name"><?php echo htmlspecialchars($user['user_name']); ?></div>
                        <div class="top-section">
                            <div class="role"><?php echo htmlspecialchars($current_role['role_name']); ?></div>/
                            <div class="year"><?php echo $career['current_term']; ?>年目の<?php echo $career['current_months']; ?>月</div>
                        </div>
                        <div class="stats-character">
                            <div class="stats">
                                <div class="stat" data-stat="trust">信頼度：<span><?php echo $user['trust_level']; ?></span></div>
                                <div class="stat" data-stat="tech">技術力：<span><?php echo $user['technical_skill']; ?></span></div>
                                <div class="stat" data-stat="negotiation">交渉力：<span><?php echo $user['negotiation_skill']; ?></span></div>
                                <div class="stat" data-stat="appearance">容姿：<span><?php echo $user['appearance']; ?></span></div>
                                <div class="stat" data-stat="likability">好感度：<span><?php echo $user['popularity']; ?></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="back">
                <div class="back-status">
                    <div class="back-top-section">
                        <div class="back-name"><?php echo htmlspecialchars($user['user_name']); ?></div>/
                        <div class="back-role"><?php echo htmlspecialchars($current_role['role_name']); ?></div>
                    </div>
                </div>
                <div class="stats-graph">
                    <canvas id="radarChart"></canvas>
                </div>
            </div>
        </div>
        <button id="toggleButton" class="back-button">裏を見る</button>
        <form action="start_event.php" method="POST">
            <button type="submit" name="start_event" class="start-button">ゲーム開始</button>
        </form>
    </div>

    <script>
        const modal = document.getElementById('myModal');
        const btn = document.getElementById('openModal');
        const span = document.getElementsByClassName('close')[0];
        const card = document.querySelector(".card");
const toggleButton = document.getElementById("toggleButton");

let isFront = true;
let radarChartInstance = null; // グローバル変数でチャートインスタンスを管理

toggleButton.addEventListener("click", () => {
    if (isFront) {
        card.style.transform = "rotateY(180deg)"; // 裏面を表示
        toggleButton.textContent = "表を見る"; // ボタンのテキストを変更
        if (!radarChartInstance) { // チャートが未生成の場合のみロード
            loadRadarChart();
        }
    } else {
        card.style.transform = "rotateY(0deg)"; // 表面を表示
        toggleButton.textContent = "裏を見る"; // ボタンのテキストを変更
    }
    isFront = !isFront; // 状態を切り替え
});

function loadRadarChart() {
    const dataValues = [
        <?php echo $user['trust_level']; ?>,
        <?php echo $user['technical_skill']; ?>,
        <?php echo $user['negotiation_skill']; ?>,
        <?php echo $user['appearance']; ?>,
        <?php echo $user['popularity']; ?>
    ];

    const ctx = document.getElementById('radarChart').getContext('2d');

    // 既存のチャートインスタンスがある場合は削除
    if (radarChartInstance) {
        radarChartInstance.destroy();
    }

    // 新しいチャートを作成
    radarChartInstance = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['信頼度', '技術力', '交渉力', '容姿', '好感度'],
            datasets: [{
                label: 'ステータス',
                data: dataValues,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#fff'
                    }
                }
            },
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    ticks: {
                        beginAtZero: true,
                        stepSize: 20,
                        color: '#fff',
                        font: {
                            size: 14
                        },
                        backdropColor: 'transparent'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.5)'
                    },
                    angleLines: {
                        color: 'rgba(255, 255, 255, 0.5)'
                    },
                    pointLabels: {
                        font: {
                            size: 16
                        },
                        color: '#fff'
                    }
                }
            }
        }
    });
}


        let radarChart;

                // ボタンを押したときにポップアップを表示する
                document.getElementById('openPopupBtn').addEventListener('click', function() {
            document.getElementById('popup').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
        });

        // ×ボタンを押したときにポップアップを閉じる
        document.getElementById('closeBtn').addEventListener('click', function() {
            document.getElementById('popup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        });

        // オーバーレイ部分をクリックしたらポップアップを閉じる
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('popup').style.display = 'none';
            document.getElementById('overlay').style.display = 'none';
        });
    </script>
</body>
</html>
