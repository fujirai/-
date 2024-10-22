// パーティクル背景の設定
particlesJS('particles-js', {
    particles: {
        number: { value: 80, density: { enable: true, value_area: 800 } },
        color: { value: "#ffffff" },
        shape: { type: "circle" },
        opacity: { value: 0.5 },
        size: { value: 3, random: true },
        move: { enable: true, speed: 5 }
    }
});

// ランキングのポップアニメーション
window.addEventListener('load', () => {
    const rankingWrapper = document.querySelector('.ranking-wrapper');
    setTimeout(() => {
        rankingWrapper.classList.add('show');
    }, 200); // 0.2秒後にアニメーションを開始
});
