<?php
session_start();
require_once __DIR__ . '/../db.php';  // DB接続ファイルをインクルード

$errorMessage = ""; // エラーメッセージを初期化

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // フォームから送信されたデータを取得
    $user_name = $_POST['user_name'];
    $user_password = $_POST['user_password'];

    try {
        // DB接続
        $conn = connectDB(); // db.php で定義されている connectDB 関数を使って接続

        // ユーザー名でユーザーを検索
        $stmt = $conn->prepare("SELECT user_name, password FROM User WHERE user_name = ?");
        $stmt->bindParam(1, $user_name, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "クエリ成功: ユーザーが見つかりました<br>";
        } else {
            echo "クエリ失敗: ユーザーが見つかりません<br>";
        }

        // ユーザーが見つかった場合
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            $db_password = $row['password'];

            // パスワードの確認（入力された社畜番号をハッシュ化して照合）
            if (password_verify($user_password, $db_password)) {
                // ログイン成功
                $_SESSION['user_name'] = $user_name; // セッションにユーザー名を保存
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
    <link rel="stylesheet" href="css/login-2.css">
    <title>ログイン画面</title>
</head>
<body>
<div class="login-box">
        <h2>ログイン</h2>
        <form id="loginForm" method="POST">
        <input type="text" id="name" name="user_name" placeholder="名前 (10文字以内)" max="10" required>
        <input type="number" id="number" name="user_password" placeholder="社畜番号 (6桁)" min="100000" max="999999" required>

            <a href="../G1-1/register.php">ログインできない方はこちら</a>
            <div class="button-group">
                <button type="submit" class="confirm-button">決定</button>
                <button type="button" class="back-button" onclick="goBack()">戻る</button>
            </div>
        </form>
</div>

<script>
    // フォームの送信時にバリデーションを行い、エラーがない場合はバックエンドにデータを送信
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        event.preventDefault();  // フォームのデフォルト送信を防止
        const name = document.getElementById('name').value.trim();
        const number = document.getElementById('number').value.trim();
        const nameError = document.getElementById('nameError');
        const numberError = document.getElementById('numberError');

        // エラーメッセージを初期化
        nameError.textContent = '';
        numberError.textContent = '';

        let isValid = true;

        // 名前が10文字以内かのチェック
        if (name === '' || name.length > 10) {
            nameError.textContent = '名前は10文字以内で入力してください。';
            isValid = false;
        }

        // 番号が6桁かのチェック
        if (number === '' || number.length !== 6) {
            numberError.textContent = '番号は6桁で入力してください。';
            isValid = false;
        }

        if (isValid) {
            // バリデーションが通った場合にフォームを送信
            this.submit();
        }
    });

    // 戻るボタンのクリックイベント処理
    function goBack() {
        window.location.href = '../G1-0/index.html';
    }
</script>
</body>
</html>