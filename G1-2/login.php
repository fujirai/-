<?php
session_start();
require_once __DIR__ . '/../db.php';  // DB接続ファイルをインクルード

$errorMessage = ""; // エラーメッセージを初期化

$Login = false;

//ログイン状況の確認
if (isset($_SESSION['user_id'])) {
    $Login = true;
}

// ログアウト処理
if (isset($_GET['logout'])) {
    // セッションの破棄
    $_SESSION = array();
    session_destroy(); 
    // ログイン画面にリダイレクト
    header("Location: login.php"); 
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // フォームから送信されたデータを取得
    $user_name = $_POST['user_name'];
    $user_password = $_POST['user_password'];

    try {
        // DB接続
        $conn = connectDB(); // db.php で定義されている connectDB 関数を使って接続

        // ユーザー名でユーザーを検索
        $stmt = $conn->prepare("SELECT user_name, password, user_id FROM User WHERE user_name = :user_name");
        $stmt->execute([':user_name' => $user_name]);

        // ユーザーが見つかった場合
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $db_password = $row['password'];

            // パスワードの確認
            if (password_verify($user_password, $db_password)) {
                // ログイン成功
                $_SESSION['user_id'] = $row['user_id'];  // セッションに user_id を保存
                $_SESSION['user_name'] = $row['user_name']; // セッションにユーザー名を保存
                header("Location: ../G2-1/home.php"); // home.php に遷移
                exit();
            } else {
                $errorMessage = "社畜番号が間違っています。";
            }
        } else {
            $errorMessage = "ユーザー名が見つかりません。";
        }
    } catch (PDOException $e) {
        // DB接続エラーが発生した場合
        $errorMessage = "データベース接続エラー: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css">
    <title>ログイン画面</title>
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
<div class="login-box">
        <h2>ログイン</h2>
        <form id="loginForm" method="POST">
            <p class="name">名前<br>
                <input type="text" id="name" name="user_name" placeholder="名前 (10文字以内)" maxlength="10" required oninput="validateName()">
            </p>
            <p class="number">社畜番号<br>
                <input type="password" maxlength="6" id="number" name="user_password" placeholder="社畜番号 (数字6桁)" required oninput="validatePassword()">
            </p>
            <a href="../G1-1/register.php">ログインできない方はこちら</a>
            <div class="button-group">
                <button type="submit" id="submitButton" class="confirm-button">決定</button>
                <button type="button" class="back-button" onclick="goBack()">戻る</button>
            </div>
        </form>

        <!-- タイムアウト時のメッセージ -->
        <div id="timeoutMessage" style="display:none; color:red; font-size: 14px; margin-top: 10px;">
            ５分以内にログインしてください。
        </div>

        <!-- 既にログインしていた場合のメッセージ -->
        <div id="loginMessage" style="display:none; color:red; font-size: 14px; margin-top: 10px;">
            既にログインしています。</br>
            再度ログインしたい場合はログアウトしてください。</br>
        </div>

        <?php if ($errorMessage): ?>
            <p class="error-message"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>
</div>

<script>
    // 戻るボタンのクリックイベント処理
    function goBack() {
        window.location.href = '../G1-0/index.html';
    }


    function validateName() {
        const nameField = document.getElementById('name');
        // 名前は10文字以内、全ての文字が有効
        if (nameField.value.length > 10) {
            nameField.value = nameField.value.slice(0, 10); // 11文字目以降を削除
        }
    }


    // パスワードフィールドのリアルタイムバリデーション
    function validatePassword() {
        const passwordField = document.getElementById('number');
        // 入力値をフィルタリングして数字のみ保持
        passwordField.value = passwordField.value.replace(/[^0-9]/g, '');

        // typeを変更して表示非表示ボタンを消す
        passwordField.type = 'text';
        passwordField.type = 'password';

        // 6桁目が入力されたら表示
        if (passwordField.value.length === 6) {
            // パスワードフィールドを表示状態に変更
            passwordField.type = 'text';

            // 3秒後にパスワードを非表示に戻す
            setTimeout(() => {
                passwordField.type = 'password';
            }, 1500); // 3000ミリ秒 = 3秒
        }
    }


    // ログインしていない場合にのみタイムアウト処理を実行
    <?php if (!$Login): ?>
        // ログインしていない場合にのみタイムアウト処理を実行
        const formResetTime = 5 * 60 * 1000; // 5分 (5分 * 60秒 * 1000ミリ秒)

        function setFormTimeout() {
            setTimeout(function() {
                // フォームをリセット
                document.getElementById('loginForm').reset();

                // タイムアウトメッセージを表示
                document.getElementById('timeoutMessage').style.display = 'block';

                // 30秒後にタイムアウトメッセージを非表示にする
                setTimeout(function() {
                    document.getElementById('timeoutMessage').style.display = 'none';
                }, 30000); // 30秒 = 30000ミリ秒

                // フォームがリセットされた後に再度タイムアウト処理を実行
                setFormTimeout();
            }, formResetTime);
        }

        // 最初のタイムアウトを開始
        setFormTimeout();
    <?php endif; ?>


    // ログインしている場合、JavaScriptの関数を実行
    <?php if ($Login): ?>
    LoginMessage();
    <?php endif; ?>

    //複数アカウントでログインしようとした時に表示
    function LoginMessage(){
        //決定ボタンを押せないように設定
        document.getElementById('submitButton').disabled = true;
        //メッセージの表示
        document.getElementById('loginMessage').style.display = 'block';
        const loginMessageElement = document.getElementById('loginMessage');
        
        // ログアウトリンク
        const logoutLink = document.createElement('a');
        logoutLink.href = "login.php?logout";
        logoutLink.textContent = 'ログアウト';
        logoutLink.style.color = 'blue'; // スタイルの設定
        loginMessageElement.appendChild(logoutLink);
    }
</script>
</body>
</html>
