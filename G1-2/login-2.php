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
        <form id="loginForm">
            <p>名前</p>
            <input type="text" id="name" placeholder="名前 (10文字以内)" max="10" required>
            <span id="nameError" class="error-message"></span>

            <p>社畜番号</p>
            <input type="number" id="number" placeholder="社畜番号 (6桁)" min="100000" max="999999" required>
            <span id="numberError" class="error-message"></span>

            <div class="button-group">
                <button type="button" class="confirm-button" id="confirmButton">決定</button>
                <button type="button" class="back-button" onclick="goBack()">戻る</button>
            </div>
            <a href="../G1-1/register.php">ログインできない方はこちら</a>
    </form>
</div>

    <script>
        document.getElementById('confirmButton').addEventListener('click', function () {
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
                window.location.href = '../G2-1/home.php';
            }

        });
        // 戻るボタンのクリックイベント処理
        function goBack() {
            window.location.href = '../G1-0/index.html';
        }
    </script>
</body>
</html>