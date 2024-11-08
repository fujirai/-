<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css\choice.css">
        <title>ボーダーイベント</title>
    </head>
<body>
    <div id="popup" class="popup">
        <h2><p><span class="rotate-text">ステータス</span></p></h2>
        <!-- ここにDBからステータスを追加 -->
        <p><h2>平社員</h2></p>
        <p><h1>名前</h1></p>
        <p><h3>
            信頼度：<br>
            技術力：<br>
            交渉力：<br>
            容　姿：<br>
            好感度：<br>
        </h3></p>
    </div>
    <!--テキスト-->
    <div class="fixed-title">
    </div>
    <div class="footer-box">
        <h2>重要なイベントです。なにか選択してください。</h2>
    </div>
   <!--選択肢-->
    <div class="options">
        <button class="option-button" onclick="updateFooter(1)">1：ああああああああああああああああああ</button>
        <button class="option-button" onclick="updateFooter(2)">2：選択肢2</button>
        <button class="option-button" onclick="updateFooter(3)">3：選択肢3</button>
        <button class="option-button" onclick="updateFooter(4)">4：選択肢4</button>
    </div>
    <div id="modo" class="modo" style="display: none;">
        <button id="backButton">戻る</button>
    </div>
<script>
            var popup = document.getElementById("popup");
        popup.addEventListener("click",function(){
            popup.classList.toggle("show");
        })

        document.addEventListener("DOMContentLoaded", function () {
            const textElement = document.querySelector(".footer-box h2");
            const optionsElement = document.querySelector(".options");
            const text = textElement.textContent;
            textElement.textContent = "";
            let i = 0;

            function type() {
                if (i < text.length) {
                    textElement.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, 25); // 25msごとに1文字ずつ表示
                } else {
                    showOptions(); // テキストが全て表示されたら選択肢を表示
                }
            }

            function showOptions() {
                optionsElement.style.opacity = "1"; // フェードイン表示
            }

            type();
        });

        function updateFooter(option) {
            const footerBox = document.querySelector('.footer-box h2');
            switch (option) {
                case 1:
                    footerBox.textContent = '選択肢1が選ばれました。あ～あ';
                    break;
                case 2:
                    footerBox.textContent = '選択肢2が選ばれました。あ～あ';
                    break;
                case 3:
                    footerBox.textContent = '選択肢3が選ばれました。あ～あ';
                    break;
                case 4:
                    footerBox.textContent = '選択肢4が選ばれました。あ～あ';
                    break;
            }
            const buttons = document.querySelectorAll('.option-button');
            buttons.forEach(button => button.style.display = 'none');

            // 1秒後にhome.htmlに遷移
            setTimeout(() => {
                        const modo = document.getElementById("modo");
                        modo.style.display = "block";
                    }, 1000);

        //     function checkNextTerm() {
        //     // PHPから取得した新しいタームと月の値を使用して確認
        //     const newMonth = <?php echo $new_month; ?>;
        //     const newTerm = <?php echo $new_term; ?>;
        //     if (newMonth === 4 && newTerm > <?php echo $current_term; ?>) {
        //          // 4月で次のタームに移行した場合、「次のタームへ」ボタンを表示
        //          setTimeout(() => {
        //             const nextTermButton = document.getElementById("nextTermButton");
        //             if (nextTermButton) {
        //                 nextTermButton.style.display = "block";
        //                 const nextTerm = document.getElementById("next-term");
        //                 nextTerm.addEventListener("click", () => {
        //                     window.location.href = 'term.php';
        //                 });
        //             }
        //         }, 1000);
        //      }else {
        //         // それ以外の場合、「戻る」ボタンを表示
        //         setTimeout(() => {
        //             const modo = document.getElementById("modo");
        //             if (modo) modo.style.display = "block";
        //         }, 1000);
        //      }
        //     }

        // if (textElement) type();

            // 戻るボタンのクリックイベント
            const backButton = document.getElementById("backButton");
            backButton.addEventListener("click", function () {
                window.location.href = '../G2-1/home.php';
            });

        }
</script>
</body>
</html>