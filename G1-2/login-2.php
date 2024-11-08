<?php
session_start();
require_once __DIR__ . '/../db.php';  // DB接続ファイルをインクルード

$errorMessage = ""; // エラーメッセージを初期化


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connectDB();

    // フォームから送信された名前とパスワードを取得
    $inputName = $_POST['name'] ?? '';
    $inputPassword = $_POST['password'] ?? '';

    if ($inputName && $inputPassword) {
        try {
            // SQL準備と実行
            $sql = $conn->prepare('SELECT * FROM User WHERE user_name = :user_name');
            $sql->execute([':user_name' => $inputName]);
            $row = $sql->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // パスワード検証
                if (password_verify($inputPassword, $row['password'])) {
                    // セッションの再生成
                    session_regenerate_id(true);
                    $_SESSION['user'] = ['user_id' => $row['user_id'], 'user_name' => $row['user_name']];

                    // ログイン成功時のリダイレクト
                    header('Location: ../G2-1/home.php');
                    exit;
                } else {
                    $_SESSION['login_error'] = "パスワードが一致しません";
                }
            } else {
                $_SESSION['login_error'] = "ユーザー名が見つかりません";
            }
        } catch (PDOException $e) {
            // データベースエラーのログ記録とエラー表示
            error_log("データベース接続エラー: " . $e->getMessage());
            $_SESSION['login_error'] = "システムエラーが発生しました。後ほど再試行してください。";
        }
    } else {
        $_SESSION['login_error'] = "名前とパスワードを入力してください";
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
    <form method="POST" action="">
        <p>名前</p>
        <input type="text" name="name" id="name" placeholder="名前 (10文字以内)" maxlength="10" value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
        <span class="error-message"><?php echo $errorMessage && strpos($errorMessage, '名前') !== false ? $errorMessage : ''; ?></span>
        <p>社畜番号</p>
        <input type="password" name="password" id="password" placeholder="パスワード (6文字以内)" minlength="6" required>
        <span class="error-message"><?php echo $errorMessage && strpos($errorMessage, 'パスワード') !== false ? $errorMessage : ''; ?></span>
        <div class="button-group">
            <button type="submit" class="confirm-button">決定</button>
            <button type="button" class="back-button" onclick="window.location.href='../G1-0/index.html';">戻る</button>
        </div>
        <a href="../G1-1/register.php">ログインできない方はこちら</a>
    </form>
</div>
</body>
</html>