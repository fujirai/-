<?php
session_start();
require '../db.php';
$userID = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/gameend.css" />
        <title>オープニング画面</title>
    </head>
    <body>
        <h1>GAME CLEAR</h1>
        <div class="clear-status">
            <?php
            $statussql=$pdo->prepare("SELECT s.*, u.user_name FROM Status s JOIN User u ON s.status_id = u.status_id WHERE u.user_id = ?");
            $statussqll->execute([$userID]);
            $status = $statussqll->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="nobr">
                <p class="user_name"><?= $status['user_name'] ?></p>
                <p class="role_name">”新人社員”</p>
            </div><div class="status">
                <p class="trust_level">信頼度：”１０”</p>
                <p class="technical_skill">技術力：”２０”</p>
                <p class="negotiation_skill">交渉力：”３０”</p>
                <p class="appearance">容姿：”４０”</p>
                <p class="popularity">好感度：”５０”</p>
            </div>
        </div>
        <button class="ranking-button" onclick="location.href='../G4-3/ranking.html'">ランキング</button>
        <button class="title-button" onclick="location.href='../G1-0/index.html'">タイトル</button>
    </body>
</html>