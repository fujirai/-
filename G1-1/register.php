<?php
session_start();
require_once __DIR__ . '/../db.php';  // DB接続ファイルをインクルード

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = connectDB();
        $conn->beginTransaction();  // トランザクションの開始

        $username = $_POST['name'];
        $password = $_POST['number'];
        $password_confirm = $_POST['numberconfirm'];

        // パスワードが一致するか確認
        if ($password !== $password_confirm) {
            throw new Exception("パスワードが一致しません。");
        }

        // **PHP側での文字数チェック**
        if (mb_strlen($username) > 10) {
            throw new Exception("ユーザー名は10文字以内で入力してください。");
        }
        if (strlen($password) !== 6) {
            throw new Exception("パスワードは6文字で入力してください。");
        }

        // ユーザー名とパスワードが空でないか確認
        if (!empty($username) && !empty($password)) {
            // 1. Statusテーブルに新しいレコードを追加
            $status_query = "INSERT INTO Status (trust_level, technical_skill, negotiation_skill, appearance, popularity, total_score) 
                             VALUES (5, 5, 5, 5, 5, 25)";
            $conn->query($status_query);

            // 2. 挿入された status_id を取得
            $status_id = $conn->lastInsertId();

            // 3. Userテーブルにレコードを追加
            $user_query = "INSERT INTO User (user_name, password, status_id, role_id, login, game_situation) 
                           VALUES (:username, :password, :status_id, 1, 'yes', 'started')";
            $stmt = $conn->prepare($user_query);
            $stmt->execute([
                ':username' => $username,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':status_id' => $status_id
            ]);

            // 4. 挿入された user_id を取得し、セッションに保存
            $user_id = $conn->lastInsertId();
            $_SESSION['user_id'] = $user_id;

            // 5. Careerテーブルに初期データを挿入（4月スタート）
            $career_query = "INSERT INTO Career (user_id, current_term, current_months) 
                             VALUES (:user_id, 1, 4)";
            $career_stmt = $conn->prepare($career_query);
            $career_stmt->execute([':user_id' => $user_id]);

            // 5.5 Statusテーブルのtotal_scoreを取得し、Roleテーブルと比較して適切なrole_idとrole_nameを取得
            $status_query = "SELECT total_score FROM Status WHERE status_id = :status_id";
            $status_stmt = $conn->prepare($status_query);
            $status_stmt->execute([':status_id' => $status_id]);
            $total_score = $status_stmt->fetchColumn();

            // Roleテーブルから該当する役割を取得
            $role_query = "SELECT role_id, role_name FROM Role WHERE repuired_status <= :total_score ORDER BY repuired_status DESC LIMIT 1";
            $role_stmt = $conn->prepare($role_query);
            $role_stmt->execute([':total_score' => $total_score]);
            $role = $role_stmt->fetch(PDO::FETCH_ASSOC);

            if ($role) {
                $role_id = $role['role_id'];
                $role_name = $role['role_name'];
            } else {
                throw new Exception("適切なロールが見つかりませんでした。");
            }

            // 6. Historyテーブルに初期データを挿入
            $history_query = "INSERT INTO History (history_id, user_id, history_role, history_term, history_month) 
                              VALUES (NULL, :user_id, :history_role, 1, 4)";
            $history_stmt = $conn->prepare($history_query);
            $history_stmt->execute([
                ':user_id' => $user_id,
                ':history_role' => $role_name
            ]);

            $conn->commit();  // トランザクションをコミット

            // home.php にリダイレクト
            header("Location: ../G2-1/home.php");
            exit;
        } else {
            throw new Exception("ユーザー名とパスワードを入力してください。");
        }
    } catch (Exception $e) {
        // エラーが発生した場合、トランザクションをロールバック
        $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/register.css">
    <title>登録画面</title>
</head>
<body>
    <div class="area">
        <ul class="circles">
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>
    <div class="register">
        <h3 class="register-text">このキャラクターに名前をつけてください</h3>
        <form action="register.php" method="post">
            <div class="inputform">
                <p class="name">名前<br>
                        <input
                            id="name-input" 
                            class="name-input" 
                            type="text" 
                            placeholder="名前を入力してください(10文字以内)" 
                            name="name" 
                            maxlength="10" 
                            required
                            >
                    </p>
                    <p class="number">社畜番号<br>
                        <input 
                            id="number-input" 
                            class="number-input" 
                            type="password" 
                            placeholder="６桁の社畜番号を入力してください" 
                            name="number" 
                            pattern="\d{6}" 
                            required
                        >
                    </p>
                    <p class="numberconfirm">確認<br>
                        <input 
                            id="numberconfirm-input"
                            class="numberconfirm-input" 
                            type="password" 
                            placeholder="社畜番号を確認してください(6桁)" 
                            name="numberconfirm" 
                            pattern="\d{6}" 
                            required
                        >
                    </p>
                </div>
                <div class="button">
                    <button class="register-ok" type="submit">入社</button>
                    <button onclick="location.href='../G1-0/index.html'">戻る</button>
                </div>
            </form>
            <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <a href="../G1-2/login.php">ログインされている方はこちら</a>
        </div>
        <script>
        // 入力中の制約（リアルタイム）
        document.getElementById('name-input').addEventListener('input', function() {
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10); // 10文字以上を切り捨て
            }
        });

        document.getElementById('number-input').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6); // 数字以外を削除し6桁まで制限
        });

        document.getElementById('numberconfirm-input').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6); // 数字以外を削除し6桁まで制限
        });
    </script>
</body>
</html>